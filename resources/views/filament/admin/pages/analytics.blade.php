<x-filament-panels::page>

<style>
.an-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 20px;
}
.an-kpi {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.an-kpi-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; }
.an-kpi-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
.an-kpi-sub   { font-size: 12px; color: #9ca3af; }
.an-section-title { font-size: 14px; font-weight: 700; color: #111827; margin: 0 0 16px; }
.an-funnel-bar { background: #f3f4f6; border-radius: 8px; height: 10px; overflow: hidden; }
.an-funnel-fill { height: 100%; border-radius: 8px; transition: width .6s ease; }
.dark .an-card, .dark .an-kpi { background: #1f2937; border-color: #374151; }
.dark .an-kpi-value { color: #f9fafb; }
.dark .an-section-title { color: #f9fafb; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
    $v  = $analytics['volume']     ?? [];
    $f  = $analytics['funnel']     ?? [];
    $t  = $analytics['topics']     ?? [];
    $p  = $analytics['performance'] ?? [];
    $s  = $v['summary_30d']        ?? ['sessions' => 0, 'messages' => 0];
    $lt = $f['lead_types']         ?? [];
@endphp

{{-- ── Top action bar ─────────────────────────────────────────────────── --}}
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button wire:click="refresh"
            style="padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid #d1d5db;background:#f9fafb;color:#374151;display:inline-flex;align-items:center;gap:6px;">
        <span wire:loading.remove wire:target="refresh">↺ Refresh</span>
        <span wire:loading wire:target="refresh">Loading...</span>
    </button>
</div>

{{-- ── KPI Strip ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
    <div class="an-kpi">
        <div class="an-kpi-value" style="color:#2563eb;">{{ $f['total_sessions'] ?? 0 }}</div>
        <div class="an-kpi-label">Total Sessions</div>
        <div class="an-kpi-sub">{{ $s['sessions'] }} in last 30 days</div>
    </div>
    <div class="an-kpi">
        <div class="an-kpi-value" style="color:#16a34a;">{{ $f['total_leads'] ?? 0 }}</div>
        <div class="an-kpi-label">Leads Captured</div>
        <div class="an-kpi-sub">{{ $f['sessions_with_leads'] ?? 0 }} sessions converted</div>
    </div>
    <div class="an-kpi">
        <div class="an-kpi-value" style="color:#7c3aed;">{{ $s['messages'] }}</div>
        <div class="an-kpi-label">Messages (30d)</div>
        <div class="an-kpi-sub">{{ $p['avg_msgs_per_session'] ?? 0 }} avg per session</div>
    </div>
    <div class="an-kpi">
        <div class="an-kpi-value" style="color:{{ ($p['fallback_rate_pct'] ?? 0) > 20 ? '#dc2626' : '#d97706' }};">
            {{ $p['fallback_rate_pct'] ?? 0 }}%
        </div>
        <div class="an-kpi-label">Fallback Rate</div>
        <div class="an-kpi-sub">{{ $p['total_fallbacks'] ?? 0 }} of {{ $p['total_ai_msgs'] ?? 0 }} AI msgs</div>
    </div>
</div>

{{-- ── Row 1: Volume chart + Lead funnel ──────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:16px;">

    {{-- Volume trend (line chart) --}}
    <div class="an-card">
        <div class="an-section-title">1. Conversation Volume & Trends</div>
        <div style="position:relative;height:220px;" wire:ignore>
            <canvas id="volumeChart"></canvas>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;">
            <div>
                <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">By Hour (WIB)</div>
                <div style="position:relative;height:100px;" wire:ignore>
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">By Day of Week</div>
                <div style="position:relative;height:100px;" wire:ignore>
                    <canvas id="dowChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Lead funnel --}}
    <div class="an-card">
        <div class="an-section-title">2. Lead Conversion Funnel</div>
        @php $total = max($f['total_sessions'] ?? 1, 1); @endphp
        @foreach([
            ['label' => 'All Sessions',              'value' => $f['total_sessions']      ?? 0, 'color' => '#3b82f6'],
            ['label' => 'Sessions w/ Messages',      'value' => $f['sessions_with_msgs']  ?? 0, 'color' => '#8b5cf6'],
            ['label' => 'Sessions → Lead',           'value' => $f['sessions_with_leads'] ?? 0, 'color' => '#16a34a'],
            ['label' => 'Total Leads Created',       'value' => $f['total_leads']         ?? 0, 'color' => '#f59e0b'],
        ] as $step)
        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <span style="font-size:12px;color:#374151;font-weight:500;">{{ $step['label'] }}</span>
                <span style="font-size:13px;font-weight:700;color:{{ $step['color'] }};">{{ $step['value'] }}</span>
            </div>
            <div class="an-funnel-bar">
                <div class="an-funnel-fill"
                     style="width:{{ round($step['value'] / $total * 100) }}%;background:{{ $step['color'] }};"></div>
            </div>
            <div style="font-size:10px;color:#9ca3af;margin-top:2px;">
                {{ round($step['value'] / $total * 100) }}% of all sessions
            </div>
        </div>
        @endforeach

        @if(!empty($lt))
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6;">
            <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Lead Types</div>
            @foreach($lt as $type)
            @php $typePct = $f['total_leads'] > 0 ? round($type['cnt'] / $f['total_leads'] * 100) : 0; @endphp
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <span style="font-size:12px;color:#374151;min-width:90px;">{{ ucfirst($type['lead_type']) }}</span>
                <div style="flex:1;background:#f3f4f6;border-radius:4px;height:6px;">
                    <div style="width:{{ $typePct }}%;height:100%;border-radius:4px;background:#16a34a;"></div>
                </div>
                <span style="font-size:12px;font-weight:600;color:#374151;min-width:24px;text-align:right;">{{ $type['cnt'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Row 2: Topics + AI Performance ─────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    {{-- Topics doughnut --}}
    <div class="an-card">
        <div class="an-section-title">3. Topic / Intent Breakdown</div>
        <div style="display:grid;grid-template-columns:180px 1fr;gap:16px;align-items:center;">
            <div style="position:relative;height:180px;" wire:ignore>
                <canvas id="topicsChart"></canvas>
            </div>
            <div>
                @foreach($t as $i => $topic)
                @php
                    $colors = ['#3b82f6','#16a34a','#f59e0b','#8b5cf6','#ef4444','#06b6d4'];
                    $col = $colors[$i % count($colors)];
                    $total_topic = collect($t)->sum('session_count');
                    $pct = $total_topic > 0 ? round($topic['session_count'] / $total_topic * 100) : 0;
                @endphp
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $col }};flex-shrink:0;"></div>
                    <span style="font-size:12px;color:#374151;flex:1;">{{ $topic['topic'] }}</span>
                    <span style="font-size:12px;font-weight:700;color:#374151;">{{ $topic['session_count'] }}</span>
                    <span style="font-size:11px;color:#9ca3af;">({{ $pct }}%)</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AI Performance --}}
    <div class="an-card">
        <div class="an-section-title">4. AI Performance & Failure Analysis</div>

        {{-- Fallback rate gauge --}}
        <div style="text-align:center;margin-bottom:20px;">
            <div style="position:relative;height:130px;" wire:ignore>
                <canvas id="fallbackChart"></canvas>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px;">Fallback Rate</div>
        </div>

        {{-- Performance metrics grid --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#374151;">{{ $p['avg_msgs_per_session'] ?? 0 }}</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Avg msgs/session</div>
            </div>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#374151;">{{ $p['avg_qdrant_calls_per_session'] ?? 0 }}</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Avg RAG calls/session</div>
            </div>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#dc2626;">{{ $p['total_fallbacks'] ?? 0 }}</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Total fallbacks</div>
            </div>
            <div style="background:#f9fafb;border-radius:8px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#374151;">{{ $p['total_ai_msgs'] ?? 0 }}</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Total AI responses</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Chart.js initialization ─────────────────────────────────────────── --}}
<script>
(function() {
    const PALETTE = ['#3b82f6','#16a34a','#f59e0b','#8b5cf6','#ef4444','#06b6d4'];

    function initCharts(data) {
        if (!data || !data.volume) return;

        // Destroy existing charts
        ['volumeChart','hourlyChart','dowChart','topicsChart','fallbackChart'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el._chart) { el._chart.destroy(); }
        });

        const v = data.volume;
        const t = data.topics || [];
        const p = data.performance || {};

        // 1a. Daily volume line chart
        const volCtx = document.getElementById('volumeChart');
        if (volCtx) {
            const chart = new Chart(volCtx, {
                type: 'line',
                data: {
                    labels: v.daily.map(d => d.date),
                    datasets: [
                        {
                            label: 'Sessions',
                            data: v.daily.map(d => d.sessions),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.08)',
                            tension: 0.3, fill: true, pointRadius: 4,
                        },
                        {
                            label: 'Messages',
                            data: v.daily.map(d => d.messages),
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22,163,74,0.06)',
                            tension: 0.3, fill: true, pointRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { boxWidth: 10, font: { size: 11 } } } },
                    scales: {
                        x: { ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } }
                    }
                }
            });
            volCtx._chart = chart;
        }

        // 1b. Hourly bar chart
        const allHours = Array.from({length: 24}, (_, i) => i);
        const hourMap = {};
        v.hourly.forEach(h => { hourMap[h.hour] = h.sessions; });
        const hourCtx = document.getElementById('hourlyChart');
        if (hourCtx) {
            hourCtx._chart = new Chart(hourCtx, {
                type: 'bar',
                data: {
                    labels: allHours.map(h => h + ':00'),
                    datasets: [{ data: allHours.map(h => hourMap[h] || 0), backgroundColor: '#3b82f6', borderRadius: 3 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { font: { size: 9 }, maxRotation: 0,
                              callback: (v, i) => i % 6 === 0 ? allHours[i] + 'h' : '' } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 9 } } }
                    }
                }
            });
        }

        // 1c. Day of week bar
        const DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const dowMap = {};
        v.dow.forEach(d => { dowMap[d.dow] = d.sessions; });
        const dowCtx = document.getElementById('dowChart');
        if (dowCtx) {
            dowCtx._chart = new Chart(dowCtx, {
                type: 'bar',
                data: {
                    labels: DAYS,
                    datasets: [{ data: DAYS.map((_, i) => dowMap[i] || 0), backgroundColor: '#8b5cf6', borderRadius: 3 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { font: { size: 9 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 9 } } }
                    }
                }
            });
        }

        // 3. Topics doughnut
        const topicsCtx = document.getElementById('topicsChart');
        if (topicsCtx && t.length > 0) {
            topicsCtx._chart = new Chart(topicsCtx, {
                type: 'doughnut',
                data: {
                    labels: t.map(x => x.topic),
                    datasets: [{
                        data: t.map(x => x.session_count),
                        backgroundColor: PALETTE.slice(0, t.length),
                        borderWidth: 2, borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} sessions`
                    }}}
                }
            });
        }

        // 4. Fallback rate doughnut (gauge style)
        const fbRate = parseFloat(p.fallback_rate_pct || 0);
        const fbCtx = document.getElementById('fallbackChart');
        if (fbCtx) {
            const fbColor = fbRate > 20 ? '#ef4444' : (fbRate > 10 ? '#f59e0b' : '#16a34a');
            fbCtx._chart = new Chart(fbCtx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [fbRate, 100 - fbRate],
                        backgroundColor: [fbColor, '#f3f4f6'],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: 270,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                    }
                },
                plugins: [{
                    id: 'gauge-text',
                    afterDraw(chart) {
                        const { ctx, width, height } = chart;
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.fillStyle = fbColor;
                        ctx.font = 'bold 22px sans-serif';
                        ctx.fillText(fbRate.toFixed(1) + '%', width / 2, height - 14);
                        ctx.restore();
                    }
                }]
            });
        }
    }

    // Init on page load
    document.addEventListener('DOMContentLoaded', function() {
        initCharts(@json($analytics));
    });

    // Re-init when Livewire refreshes data
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('analytics-loaded', function(event) {
            const data = Array.isArray(event) ? event[0] : event;
            initCharts(data.data || data);
        });
    });
})();
</script>

</x-filament-panels::page>
