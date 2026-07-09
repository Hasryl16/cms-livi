<x-filament-panels::page>

    {{-- Back button --}}
    <div class="mb-4">
        <a
            href="{{ $this->backUrl() }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
        >
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Chat Logs
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chat thread --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Conversation Thread</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">{{ $sessionId }}</p>
                </div>

                <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto bg-[#f7f8fa] dark:bg-gray-950">
                    @forelse ($messages as $msg)
                        @php $isUser = in_array($msg['role'] ?? '', ['user', 'human']); @endphp
                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[78%]">
                                <div class="px-4 py-2.5 text-sm leading-relaxed rounded-2xl
                                    {{ $isUser
                                        ? 'bg-white border-2 border-gray-300 text-gray-800 rounded-full px-5'
                                        : 'bg-[#eeeeec] text-gray-800 dark:bg-gray-700 dark:text-gray-100' }}">
                                    {{ $msg['content'] ?? '' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1 {{ $isUser ? 'text-right' : 'text-left' }}">
                                    {{ isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->format('H:i') : '' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-center text-gray-400 py-8">No messages in this session.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Metadata panel --}}
        <div class="space-y-4">

            {{-- Session info --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Session Info</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400">Session ID</dt>
                        <dd class="font-mono text-gray-700 dark:text-gray-300 break-all text-xs">{{ $sessionId }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Messages</dt>
                        <dd class="text-gray-700 dark:text-gray-300">{{ $messageCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Started At</dt>
                        <dd class="text-gray-700 dark:text-gray-300">
                            {{ $startedAt ? \Carbon\Carbon::parse($startedAt)->format('d M Y, H:i') : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Lead info --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Lead Info</h3>
                @if ($lead)
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400">Name</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $lead['name'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Phone / WhatsApp</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $lead['phone'] ?? '—' }}</dd>
                        </div>
                        @if (!empty($lead['email']))
                        <div>
                            <dt class="text-xs text-gray-400">Email</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $lead['email'] }}</dd>
                        </div>
                        @endif
                        @if (!empty($lead['company']))
                        <div>
                            <dt class="text-xs text-gray-400">Company</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $lead['company'] }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs text-gray-400">Captured At</dt>
                            <dd class="text-gray-700 dark:text-gray-300">
                                {{ isset($lead['created_at']) ? \Carbon\Carbon::parse($lead['created_at'])->format('d M Y, H:i') : '—' }}
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            ✓ Lead Captured
                        </span>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500">No lead captured in this session.</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            No Lead
                        </span>
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-filament-panels::page>
