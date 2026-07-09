<x-filament-panels::page>

    {{-- Back button --}}
    <div style="margin-bottom:16px;">
        <a href="{{ $this->backUrl() }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;text-decoration:none;">
            ← Back to Chat Logs
        </a>
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:24px;">

        {{-- Chat thread --}}
        <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb;">
                <p style="font-size:14px;font-weight:600;color:#374151;margin:0;">Conversation Thread</p>
                <p style="font-size:11px;color:#6b7280;font-family:monospace;margin:2px 0 0;">{{ $sessionId }}</p>
            </div>

            <div style="padding:16px;display:flex;flex-direction:column;gap:12px;max-height:600px;overflow-y:auto;background:#f7f8fa;">
                @forelse ($messages as $msg)
                    @php $isUser = in_array($msg['role'] ?? '', ['user', 'human']); @endphp
                    <div style="display:flex;{{ $isUser ? 'justify-content:flex-end' : 'justify-content:flex-start' }};">
                        <div style="max-width:78%;">
                            <div style="padding:10px 16px;font-size:13px;line-height:1.5;border-radius:{{ $isUser ? '9999px;border:2px solid #d1d5db;background:white' : '16px;background:#eeeeec' }};color:#1f2937;">
                                {{ $msg['content'] ?? '' }}
                            </div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:4px;text-align:{{ $isUser ? 'right' : 'left' }};">
                                {{ isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->format('H:i') : '' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px;text-align:center;color:#9ca3af;padding:32px 0;">No messages in this session.</p>
                @endforelse
            </div>
        </div>

        {{-- Metadata panel --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Session info --}}
            <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;padding:16px;">
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;">Session Info</p>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Session ID</div>
                        <div style="font-family:monospace;font-size:11px;color:#374151;word-break:break-all;">{{ $sessionId }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Messages</div>
                        <div style="color:#374151;">{{ $messageCount }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Started At</div>
                        <div style="color:#374151;">{{ $startedAt ? \Carbon\Carbon::parse($startedAt)->format('d M Y, H:i') : '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Lead info --}}
            <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;padding:16px;">
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px;">Lead Info</p>
                @if ($lead)
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Name</div>
                            <div style="color:#374151;">{{ $lead['name'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">WhatsApp</div>
                            <div style="color:#374151;">{{ $lead['whatsapp'] ?? '—' }}</div>
                        </div>
                        @if (!empty($lead['notes']))
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Notes</div>
                            <div style="color:#374151;">{{ $lead['notes'] }}</div>
                        </div>
                        @endif
                        @if (!empty($lead['lead_type']))
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Type</div>
                            <div style="color:#374151;">{{ $lead['lead_type'] }}</div>
                        </div>
                        @endif
                        @if (!empty($lead['company']))
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Company</div>
                            <div style="color:#374151;">{{ $lead['company'] }}</div>
                        </div>
                        @endif
                        <div>
                            <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Captured At</div>
                            <div style="color:#374151;">{{ isset($lead['created_at']) ? \Carbon\Carbon::parse($lead['created_at'])->format('d M Y, H:i') : '—' }}</div>
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534;">
                            ✓ Lead Captured
                        </span>
                    </div>
                @else
                    <p style="font-size:13px;color:#9ca3af;margin:0;">No lead captured in this session.</p>
                    <div style="margin-top:8px;">
                        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:600;background:#f3f4f6;color:#374151;">
                            No Lead
                        </span>
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-filament-panels::page>
