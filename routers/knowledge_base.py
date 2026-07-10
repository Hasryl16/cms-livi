import os
from pathlib import Path
from typing import Optional

import asyncpg
from fastapi import APIRouter, BackgroundTasks, Depends, File, HTTPException, UploadFile
from asyncpg import Connection
from db import get_db

from workers.ingestion import run_ingestion

router = APIRouter()

UPLOAD_DIR = Path(__file__).parent.parent / "uploads"
UPLOAD_DIR.mkdir(exist_ok=True)
MAX_FILE_SIZE = 50 * 1024 * 1024  # 50 MB
ALLOWED_TYPES = {"pdf", "docx", "xlsx"}


def _ext(filename: str) -> str:
    return Path(filename).suffix.lstrip(".").lower()


@router.post("/knowledge-base/ingest")
async def ingest_document(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(...),
    db: Connection = Depends(get_db),
):
    ext = _ext(file.filename or "")
    if ext not in ALLOWED_TYPES:
        raise HTTPException(400, f"Unsupported file type '{ext}'. Allowed: pdf, docx, xlsx")

    content = await file.read()
    if len(content) > MAX_FILE_SIZE:
        raise HTTPException(400, "File exceeds 50 MB limit")

    # Versioning: find any active doc with the same filename
    existing = await db.fetchrow(
        """SELECT id, version FROM knowledge_base_documents
           WHERE filename = $1 AND status != 'superseded'
           ORDER BY version DESC LIMIT 1""",
        file.filename,
    )
    new_version = 1
    supersedes_id: Optional[int] = None
    if existing:
        supersedes_id = existing["id"]
        new_version = existing["version"] + 1
        await db.execute(
            "UPDATE knowledge_base_documents SET status='superseded' WHERE id=$1",
            supersedes_id,
        )

    doc_id = await db.fetchval(
        """INSERT INTO knowledge_base_documents
           (filename, file_type, status, version, supersedes_id, uploaded_by)
           VALUES ($1, $2, 'processing', $3, $4, 'admin')
           RETURNING id""",
        file.filename, ext, new_version, supersedes_id,
    )

    filepath = str(UPLOAD_DIR / f"{doc_id}_{file.filename}")
    with open(filepath, "wb") as f:
        f.write(content)

    background_tasks.add_task(run_ingestion, doc_id, filepath, ext)

    return {"doc_id": doc_id, "status": "processing", "version": new_version}


@router.get("/knowledge-base/documents")
async def list_documents(db: Connection = Depends(get_db)):
    rows = await db.fetch(
        """SELECT id, filename, file_type, status, version, supersedes_id,
                  chunk_count, uploaded_by, uploaded_at::text, indexed_at::text, error_message
           FROM knowledge_base_documents
           ORDER BY uploaded_at DESC"""
    )
    docs = [dict(r) for r in rows]
    stats = {
        "total": len(docs),
        "processing": sum(1 for d in docs if d["status"] == "processing"),
        "failed": sum(1 for d in docs if d["status"] == "failed"),
        "indexed": sum(1 for d in docs if d["status"] == "indexed"),
    }
    return {"documents": docs, "stats": stats}


@router.post("/knowledge-base/{doc_id}/rollback")
async def rollback_document(
    doc_id: int,
    background_tasks: BackgroundTasks,
    db: Connection = Depends(get_db),
):
    doc = await db.fetchrow(
        "SELECT * FROM knowledge_base_documents WHERE id=$1", doc_id
    )
    if not doc:
        raise HTTPException(404, "Document not found")
    if doc["status"] != "indexed":
        raise HTTPException(400, f"Cannot rollback document with status '{doc['status']}'")
    if not doc["supersedes_id"]:
        raise HTTPException(400, "No previous version to rollback to")

    prev = await db.fetchrow(
        "SELECT * FROM knowledge_base_documents WHERE id=$1", doc["supersedes_id"]
    )
    if not prev:
        raise HTTPException(404, "Previous version not found")

    await db.execute(
        "UPDATE knowledge_base_documents SET status='superseded' WHERE id=$1", doc_id
    )
    await db.execute(
        "UPDATE knowledge_base_documents SET status='processing', error_message=NULL WHERE id=$1",
        prev["id"],
    )
    prev_filepath = str(UPLOAD_DIR / f"{prev['id']}_{prev['filename']}")
    background_tasks.add_task(run_ingestion, prev["id"], prev_filepath, prev["file_type"])

    return {"message": "Rollback started", "restoring_version": prev["version"]}


@router.post("/knowledge-base/{doc_id}/retry")
async def retry_document(
    doc_id: int,
    background_tasks: BackgroundTasks,
    db: Connection = Depends(get_db),
):
    doc = await db.fetchrow(
        "SELECT * FROM knowledge_base_documents WHERE id=$1", doc_id
    )
    if not doc:
        raise HTTPException(404, "Document not found")
    if doc["status"] != "failed":
        raise HTTPException(400, f"Cannot retry document with status '{doc['status']}'")

    await db.execute(
        "UPDATE knowledge_base_documents SET status='processing', error_message=NULL WHERE id=$1",
        doc_id,
    )
    filepath = str(UPLOAD_DIR / f"{doc_id}_{doc['filename']}")
    background_tasks.add_task(run_ingestion, doc_id, filepath, doc["file_type"])

    return {"message": "Retry started"}


@router.post("/knowledge-base/search")
async def search_knowledge_base(body: dict):
    """Embed a query with Cohere and return top-k matching chunks from Qdrant."""
    from utils.qdrant_socket_patch import apply as _apply_socket_patch
    _apply_socket_patch()

    query = (body.get("query") or "").strip()
    top_k = min(int(body.get("top_k", 5)), 20)

    if not query:
        raise HTTPException(400, "query is required")

    cohere_key = os.getenv("COHERE_API_KEY", "")
    if not cohere_key:
        raise HTTPException(500, "COHERE_API_KEY not configured")

    qdrant_url = os.getenv("QDRANT_URL", "")
    if not qdrant_url:
        raise HTTPException(500, "QDRANT_URL not configured")

    try:
        import cohere as cohere_lib
        co = cohere_lib.AsyncClientV2(cohere_key)
        embed_resp = await co.embed(
            texts=[query],
            model="embed-multilingual-v3.0",
            input_type="search_query",
            embedding_types=["float"],
        )
        query_vector = embed_resp.embeddings.float_[0]
    except Exception as e:
        raise HTTPException(500, f"Cohere embedding failed: {e}")

    try:
        from qdrant_client import QdrantClient
        collection = os.getenv("QDRANT_COLLECTION", "livi_cohere")
        qdrant_api_key = os.getenv("QDRANT_API_KEY") or None
        qc = QdrantClient(url=qdrant_url, api_key=qdrant_api_key)
        response = qc.query_points(
            collection_name=collection,
            query=query_vector,
            limit=top_k,
            with_payload=True,
        )
        hits = response.points
    except Exception as e:
        raise HTTPException(500, f"Qdrant search failed: {e}")

    return {
        "query": query,
        "collection": os.getenv("QDRANT_COLLECTION", "livi_cohere"),
        "results": [
            {
                "score": round(hit.score, 4),
                "doc_id": hit.payload.get("doc_id"),
                "filename": hit.payload.get("filename"),
                "version": hit.payload.get("version"),
                "chunk_index": hit.payload.get("chunk_index"),
                "text": hit.payload.get("text", ""),
            }
            for hit in hits
        ],
    }


@router.delete("/knowledge-base/{doc_id}")
async def delete_document(
    doc_id: int,
    db: Connection = Depends(get_db),
):
    """Hard-delete a document record and remove its vectors from Qdrant."""
    from utils.qdrant_socket_patch import apply as _apply_socket_patch
    _apply_socket_patch()

    doc = await db.fetchrow(
        "SELECT * FROM knowledge_base_documents WHERE id=$1", doc_id
    )
    if not doc:
        raise HTTPException(404, "Document not found")

    qdrant_url = os.getenv("QDRANT_URL", "")
    if qdrant_url:
        try:
            from qdrant_client import QdrantClient
            from qdrant_client.models import Filter, FieldCondition, MatchValue
            collection = os.getenv("QDRANT_COLLECTION", "livi_cohere")
            qdrant_api_key = os.getenv("QDRANT_API_KEY") or None
            qc = QdrantClient(url=qdrant_url, api_key=qdrant_api_key)
            qc.delete(
                collection_name=collection,
                points_selector=Filter(
                    must=[FieldCondition(key="doc_id", match=MatchValue(value=doc_id))]
                ),
            )
        except Exception:
            pass

    await db.execute("DELETE FROM knowledge_base_documents WHERE id=$1", doc_id)
    return {"message": f"Document {doc_id} deleted"}
