<x-filament-panels::page>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-4 dark:bg-gray-900 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Search --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search message content..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                />
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">From</label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                />
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">To</label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                />
            </div>

            {{-- Lead Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Lead Status</label>
                <select
                    wire:model.live="leadStatus"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All</option>
                    <option value="lead_captured">Lead Captured</option>
                    <option value="no_lead">No Lead</option>
                    <option value="abandoned">Abandoned</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <button
                wire:click="resetFilters"
                class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 underline"
            >
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden dark:bg-gray-900 dark:border-gray-700">
        @if (count($sessions) === 0)
            <div class="py-16 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                <p class="text-sm font-medium">No chat sessions found</p>
                <p class="text-xs mt-1">Try adjusting your filters or date range.</p>
            </div>
        @else
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Session ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">First Message</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Messages</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Lead Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($sessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                {{ Str::limit($session['session_id'], 16) }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
                                <span class="line-clamp-2">{{ $session['first_message'] ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                {{ $session['msg_count'] ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if (!empty($session['lead_id']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Lead Captured
                                    </span>
                                @elseif (($session['msg_count'] ?? 0) <= 1)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Abandoned
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        No Lead
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                                {{ $session['started_at'] ? \Carbon\Carbon::parse($session['started_at'])->format('d M Y, H:i') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ $this->detailUrl($session['session_id']) }}"
                                    class="text-xs font-semibold text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200"
                                >
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if (($paginationMeta['total_pages'] ?? 1) > 1)
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Page {{ $paginationMeta['page'] ?? 1 }} of {{ $paginationMeta['total_pages'] ?? 1 }}
                        &middot; {{ $paginationMeta['total'] ?? 0 }} sessions
                    </span>
                    <div class="flex gap-2">
                        <button
                            wire:click="prevPage"
                            @if (($currentPage ?? 1) <= 1) disabled @endif
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            ← Prev
                        </button>
                        <button
                            wire:click="nextPage"
                            @if (($currentPage ?? 1) >= ($paginationMeta['total_pages'] ?? 1)) disabled @endif
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>

</x-filament-panels::page>
