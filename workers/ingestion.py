import os
import uuid
import asyncpg
from utils.qdrant_socket_patch import apply as _apply_socket_patch

_apply_socket_patch()  # Bypass Cloudflare WAF; force IPv4 for Qdrant connections


async def run_ingestion(doc_id: int, filepath: str, file_type: str):
    """Background ingestion task — runs after HTTP response is sent."""
    db_url = os.getenv("DATABASE_URL")
    conn = await asyncpg.connect(db_url)
    try:
        await conn.execute(
            "UPDATE knowledge_base_documents SET status='processing' WHERE id=$1", doc_id
        )

        # ── 1. Extract text ──────────────────────────────────────────────
        text = ""
        try:
            if file_type == "pdf":
                import pdfplumber
                with pdfplumber.open(filepath) as pdf:
                    text = "\n".join(p.extract_text() or "" for p in pdf.pages)
            elif file_type == "docx":
                from docx import Document
                doc = Document(filepath)
                text = "\n".join(p.text for p in doc.paragraphs)
            elif file_type == "xlsx":
                import pandas as pd
                dfs = pd.read_excel(filepath, sheet_name=None)
                parts = []
                for sheet, df in dfs.items():
                    parts.append(f"Sheet: {sheet}\n" + df.fillna("").astype(str).to_csv(index=False))
                text = "\n".join(parts)
        except Exception as e:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                f"Text extraction failed: {e}", doc_id,
            )
            return

        if not text.strip():
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                "No text content extracted from document", doc_id,
            )
            return

        # ── 2. Chunk text (≈500 words, 50-word overlap) ──────────────────
        words = text.split()
        chunk_size, overlap = 500, 50
        chunks = []
        i = 0
        while i < len(words):
            chunks.append(" ".join(words[i : i + chunk_size]))
            i += chunk_size - overlap
        if not chunks:
            chunks = [text]

        # TODO: OCR for embedded images (pytesseract) would go here

        # ── 3. Cohere embeddings ─────────────────────────────────────────
        cohere_key = os.getenv("COHERE_API_KEY", "")
        if not cohere_key:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                "COHERE_API_KEY not configured in environment", doc_id,
            )
            return

        try:
            import cohere
            co = cohere.AsyncClientV2(cohere_key)
            resp = await co.embed(
                texts=chunks,
                model="embed-multilingual-v3.0",
                input_type="search_document",
                embedding_types=["float"],
            )
            embeddings = resp.embeddings.float_
        except Exception as e:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                f"Cohere embedding failed: {e}", doc_id,
            )
            return

        # ── 4. Qdrant upsert ─────────────────────────────────────────────
        qdrant_url = os.getenv("QDRANT_URL", "")
        if not qdrant_url:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                "QDRANT_URL not configured in environment", doc_id,
            )
            return

        try:
            from qdrant_client import QdrantClient
            from qdrant_client.models import PointStruct, Filter, FieldCondition, MatchValue

            qdrant_api_key = os.getenv("QDRANT_API_KEY") or None
            qc = QdrantClient(url=qdrant_url, api_key=qdrant_api_key)

            # Delete existing vectors for this doc_id (safe for retry/rollback re-index)
            try:
                collection = os.getenv("QDRANT_COLLECTION", "livi_cohere")
                qc.delete(
                    collection_name=collection,
                    points_selector=Filter(
                        must=[FieldCondition(key="doc_id", match=MatchValue(value=doc_id))]
                    ),
                )
            except Exception:
                pass  # Collection may not have this doc yet

            row = await conn.fetchrow(
                "SELECT filename, version FROM knowledge_base_documents WHERE id=$1", doc_id
            )
            filename = row["filename"] if row else ""
            version = row["version"] if row else 1

            points = [
                PointStruct(
                    id=str(uuid.uuid4()),
                    vector=embeddings[i],
                    payload={
                        "doc_id": doc_id,
                        "version": version,
                        "filename": filename,
                        "chunk_index": i,
                        "text": chunks[i],
                    },
                )
                for i in range(len(chunks))
            ]
            qc.upsert(collection_name=collection, points=points)
        except Exception as e:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                f"Qdrant upsert failed: {e}", doc_id,
            )
            return

        # ── 5. Mark indexed ──────────────────────────────────────────────
        await conn.execute(
            """UPDATE knowledge_base_documents
               SET status='indexed', chunk_count=$1, indexed_at=NOW()
               WHERE id=$2""",
            len(chunks), doc_id,
        )

    except Exception as e:
        try:
            await conn.execute(
                "UPDATE knowledge_base_documents SET status='failed', error_message=$1 WHERE id=$2",
                f"Unexpected error: {e}", doc_id,
            )
        except Exception:
            pass
    finally:
        await conn.close()
