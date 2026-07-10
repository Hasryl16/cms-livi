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
.ai-input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 13px;
    outline: none;
    background: #fff;
    color: #111827;
}
.ai-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.dark .ai-card {
    background: #1f2937;
    border-color: #374151;
}
.dark .ai-table thead tr {
    background: #111827;
    border-bottom-color: #374151;
}
.dark .ai-table tbody tr {
    border-bottom-color: #1f2937;
}
.dark .ai-input {
    background: #111827;
    border-color: #374151;
    color: #f9fafb;
}
.dark .ai-label {
    color: #9ca3af;
}
</style>

    {{-- Filters --}}
    <div class="ai-card" style="padding:16px 20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label class="ai-label">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Search message content..." class="ai-input" />
            </div>
            <div>
                <label class="ai-label">From</label>
                <input type="date" wire:model.live="dateFrom" class="ai-input" />
            </div>
            <div>
                <label class="ai-label">To</label>
                <input type="date" wire:model.live="dateTo" class="ai-input" />
            </div>
            <div>
                <label class="ai-label">Lead Status</label>
                <select wire:model.live="leadStatus" class="ai-input">
                    <option value="">All</option>
                    <option value="lead_captured">Lead Captured</option>
                    <option value="no_lead">No Lead</option>
                    <option value="abandoned">Abandoned</option>
                </select>
            </div>
        </div>
        <div style="text-align:right;margin-top:8px;">
            <button wire:click="resetFilters"
                    style="font-size:12px;color:#6b7280;background:none;border:none;cursor:pointer;text-decoration:underline;">
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="ai-card" style="overflow:hidden;">
        @if (count($sessions) === 0)
            <div style="padding:64px 24px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">💬</div>
                <p style="font-size:14px;font-weight:500;color:#6b7280;margin:0;">No chat sessions found</p>
                <p style="font-size:12px;margin-top:4px;color:#9ca3af;">Try adjusting your filters or date range.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="ai-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Session ID</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">First Message</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Msgs</th>
                            <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                            <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Date</th>
                            <th style="padding:10px 16px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td style="padding:12px 16px;color:#374151;font-family:monospace;font-size:12px;">
                                    {{ Str::limit($session['session_id'] ?? '', 16) }}
                                </td>
                                <td style="padding:12px 16px;color:#374151;max-width:300px;">
                                    <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $session['first_message'] ?? '—' }}
                                    </span>
                                </td>
                                <td style="padding:12px 16px;text-align:center;color:#374151;">
                                    {{ $session['msg_count'] ?? 0 }}
                                </td>
                                <td style="padding:12px 16px;text-align:center;">
                                    @if (!empty($session['lead_id']))
                                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534;">Lead Captured</span>
                                    @elseif (($session['msg_count'] ?? 0) <= 1)
                                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;background:#fef9c3;color:#854d0e;">Abandoned</span>
                                    @else
                                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;background:#f3f4f6;color:#374151;">No Lead</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px;color:#6b7280;font-size:12px;white-space:nowrap;">
                                    {{ isset($session['started_at']) ? \Carbon\Carbon::parse($session['started_at'])->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : '—' }}
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="{{ $this->detailUrl($session['session_id']) }}"
                                       style="font-size:12px;font-weight:600;color:#16a34a;text-decoration:none;">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if (($paginationMeta['total_pages'] ?? 1) > 1)
                <div style="padding:12px 16px;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:#6b7280;">
                        Page {{ $paginationMeta['page'] ?? 1 }} of {{ $paginationMeta['total_pages'] ?? 1 }}
                        &middot; {{ $paginationMeta['total'] ?? 0 }} sessions
                    </span>
                    <div style="display:flex;gap:8px;">
                        <button wire:click="prevPage"
                                @if (($currentPage ?? 1) <= 1) disabled @endif
                                style="padding:6px 12px;font-size:12px;border:1px solid #d1d5db;border-radius:8px;background:white;cursor:pointer;">
                            ← Prev
                        </button>
                        <button wire:click="nextPage"
                                @if (($currentPage ?? 1) >= ($paginationMeta['total_pages'] ?? 1)) disabled @endif
                                style="padding:6px 12px;font-size:12px;border:1px solid #d1d5db;border-radius:8px;background:white;cursor:pointer;">
                            Next →
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>

</x-filament-panels::page>
