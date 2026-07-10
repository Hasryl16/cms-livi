<x-filament-panels::page>

<style>
.ai-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}
.ai-table thead tr {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.ai-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
}
.ai-btn {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.ai-btn-primary  { background: #16a34a; color: #fff; }
.ai-btn-secondary { background: #f3f4f6; color: #374151; }
.ai-btn-warning  { background: #fef3c7; color: #92400e; }
.ai-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 50;
    display: flex; align-items: center; justify-content: center;
}
.ai-modal {
    background: white;
    border-radius: 16px;
    padding: 28px;
    width: 100%; max-width: 480px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.dark .ai-card           { background: #1f2937; border-color: #374151; }
.dark .ai-table thead tr { background: #111827; border-bottom-color: #374151; }
.dark .ai-table tbody tr { border-bottom-color: #1f2937; }
.dark .ai-btn-secondary  { background: #374151; color: #f9fafb; }
.dark .ai-modal          { background: #1f2937; }
</style>

@php $hasProcessing = collect($documents)->contains(fn($d) => $d['status'] === 'processing'); @endphp

<div @if($hasProcessing) wire:poll.5000ms="loadDocuments" @endif>

    {{-- Action bar --}}
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-bottom:20px;">
        <button wire:click="refresh" class="ai-btn ai-btn-secondary">↺ Refresh</button>
        <button wire:click="openUploadModal" class="ai-btn ai-btn-primary">+ Upload Document</button>
    </div>

    {{-- Stats bar --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
        <div class="ai-card" style="padding:16px;text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#374151;">{{ $stats['total'] }}</div>
            <div style="font-size:11px;color:#6b7280;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">Total</div>
        </div>
        <div class="ai-card" style="padding:16px;text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#16a34a;">{{ $stats['indexed'] }}</div>
            <div style="font-size:11px;color:#6b7280;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">Indexed</div>
        </div>
        <div class="ai-card" style="padding:16px;text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#d97706;">{{ $stats['processing'] }}</div>
            <div style="font-size:11px;color:#6b7280;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">Processing</div>
        </div>
        <div class="ai-card" style="padding:16px;text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#dc2626;">{{ $stats['failed'] }}</div>
            <div style="font-size:11px;color:#6b7280;margin-top:2px;text-transform:uppercase;letter-spacing:.05em;">Failed</div>
        </div>
    </div>

    {{-- Document table --}}
    <div class="ai-card" style="overflow:hidden;">
        @if(count($documents) === 0)
            <div style="padding:64px 24px;text-align:center;">
                <div style="font-size:40px;margin-bottom:8px;">📚</div>
                <p style="font-size:14px;font-weight:500;color:#6b7280;margin:0;">No documents uploaded yet</p>
                <p style="font-size:12px;margin-top:4px;color:#9ca3af;">Upload your first document to get started.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="ai-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Filename</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Type</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Version</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Chunks</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Uploaded</th>
                            <th style="padding:10px 16px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                        @php $isSuperseded = $doc['status'] === 'superseded'; @endphp
                        <tr style="{{ $isSuperseded ? 'opacity:0.45;' : '' }}">
                            <td style="padding:12px 16px;color:#374151;max-width:240px;">
                                <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $doc['filename'] }}">
                                    {{ $doc['filename'] }}
                                </span>
                                @if(!empty($doc['error_message']))
                                    <span style="font-size:11px;color:#dc2626;display:block;margin-top:2px;" title="{{ $doc['error_message'] }}">
                                        {{ Str::limit($doc['error_message'], 64) }}
                                    </span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center;">
                                @php
                                    $typeStyle = match($doc['file_type']) {
                                        'pdf'  => 'background:#fee2e2;color:#991b1b',
                                        'docx' => 'background:#dbeafe;color:#1e40af',
                                        'xlsx' => 'background:#dcfce7;color:#166534',
                                        default => 'background:#f3f4f6;color:#374151',
                                    };
                                @endphp
                                <span style="{{ $typeStyle }};padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;text-transform:uppercase;">
                                    {{ $doc['file_type'] }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;text-align:center;color:#374151;font-size:12px;">
                                v{{ $doc['version'] }}
                            </td>
                            <td style="padding:12px 16px;text-align:center;color:#374151;">
                                {{ $doc['chunk_count'] ?? '—' }}
                            </td>
                            <td style="padding:12px 16px;text-align:center;">
                                @php
                                    [$sbg, $sfg, $slabel] = match($doc['status']) {
                                        'pending'    => ['#f3f4f6', '#374151', 'Pending'],
                                        'processing' => ['#fef3c7', '#92400e', '⏳ Processing'],
                                        'indexed'    => ['#dcfce7', '#166534', '✓ Indexed'],
                                        'failed'     => ['#fee2e2', '#991b1b', '✗ Failed'],
                                        'superseded' => ['#f3f4f6', '#6b7280', 'Superseded'],
                                        default      => ['#f3f4f6', '#374151', $doc['status']],
                                    };
                                @endphp
                                <span style="background:{{ $sbg }};color:{{ $sfg }};padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;">
                                    {{ $slabel }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;color:#6b7280;font-size:12px;">
                                <div>{{ $doc['uploaded_by'] }}</div>
                                <div>{{ isset($doc['uploaded_at']) ? \Carbon\Carbon::parse($doc['uploaded_at'])->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : '—' }}</div>
                            </td>
                            <td style="padding:12px 16px;text-align:right;white-space:nowrap;">
                                @if($doc['status'] === 'indexed' && !empty($doc['supersedes_id']))
                                    <button wire:click="rollback({{ $doc['id'] }})"
                                            wire:confirm="Roll back to previous version? This will re-index the older version."
                                            class="ai-btn ai-btn-warning">
                                        ↩ Rollback
                                    </button>
                                @elseif($doc['status'] === 'failed')
                                    <button wire:click="retry({{ $doc['id'] }})"
                                            class="ai-btn ai-btn-secondary">
                                        ↺ Retry
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

{{-- Upload modal --}}
@if($showUploadModal)
<div class="ai-modal-backdrop" wire:click.self="closeUploadModal">
    <div class="ai-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Upload Knowledge Base Document</h3>
            <button wire:click="closeUploadModal" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;">×</button>
        </div>

        <form wire:submit.prevent="uploadDocument" enctype="multipart/form-data">
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Select File</label>
                <input type="file"
                       name="document"
                       accept=".pdf,.docx,.xlsx"
                       style="display:block;width:100%;font-size:13px;color:#374151;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;" />
            </div>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;margin-bottom:20px;font-size:12px;color:#6b7280;line-height:1.6;">
                <div>Supported: <strong>PDF, DOCX, XLSX</strong> &middot; Max size: <strong>50 MB</strong></div>
                <div>Re-uploading a file with the same name creates a new version.</div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" wire:click="closeUploadModal" class="ai-btn ai-btn-secondary">Cancel</button>
                <button type="submit" class="ai-btn ai-btn-primary">Upload & Process</button>
            </div>
        </form>
    </div>
</div>
@endif

</x-filament-panels::page>
