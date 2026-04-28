<div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}"
                    class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1 text-sm">
                    ← Back to Dashboard
                </a>
            </div>
            <h1
                class="text-4xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mt-4">
                🌐 IP Intelligence Profile
            </h1>
        </div>

        <!-- IP Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🖥️</span>
                        <h2 class="text-2xl font-mono font-bold text-slate-800">{{ $ip }}</h2>
                    </div>
                    <p class="text-slate-500 mt-1">Behavioral Analysis & Risk Profile</p>
                </div>
                <div class="flex gap-2">
                    <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-medium">Detection
                        Only</span>
                    <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-medium">No
                        Blocking</span>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
            @php
                $statCards = [
                    ['label' => 'Total Attacks', 'value' => $stats['total_attacks'], 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
                    ['label' => 'Avg Risk Score', 'value' => $stats['avg_score'], 'color' => 'text-purple-600', 'bg' => 'bg-purple-50'],
                    ['label' => 'Max Risk', 'value' => $stats['max_score'], 'color' => 'text-rose-600', 'bg' => 'bg-rose-50'],
                    ['label' => 'Last Seen', 'value' => optional($stats['last_seen'])->diffForHumans() ?? 'N/A', 'color' => 'text-slate-600', 'bg' => 'bg-slate-50'],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold {{ $card['color'] }} mt-2">
                        {{ $card['value'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <!-- Charts Row -->
        <div class="grid lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">📈 Risk Trend Over Time</h3>
                <canvas id="riskChart" class="w-full h-64"></canvas>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">⚔️ Attack Type Breakdown</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($attackTypes as $type => $count)
                        <div class="flex justify-between items-center p-2 border-b border-slate-100">
                            <span class="text-slate-700">{{ $type }}</span>
                            <span class="font-bold text-slate-800">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-slate-500 text-center py-8">No attack data for this IP</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-semibold text-slate-800 mb-5 flex items-center gap-2">🕒 Activity Timeline</h3>
            <div class="space-y-4">
                @forelse($logs as $log)
                    <div class="border-l-4 border-indigo-400 pl-4 pb-2">
                        <div class="flex flex-wrap justify-between items-start gap-2">
                            <div class="font-semibold text-slate-800">{{ $log->type }}</div>
                            <span
                                class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">
                            Score: <span class="font-mono font-bold">{{ $log->score }}</span> |
                            Attempts: {{ $log->attempts }}
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-center py-8">No activity recorded for this IP</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:init', () => {
                const ctx = document.getElementById('riskChart')?.getContext('2d');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json(array_keys($chartData->toArray())),
                        datasets: [{
                            label: 'Risk Score',
                            data: @json(array_values($chartData->toArray())),
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.05)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#4f46e5',
                            pointBorderColor: '#fff',
                            pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { labels: { color: '#1e293b' } },
                            tooltip: { backgroundColor: '#fff', titleColor: '#1e293b', bodyColor: '#475569' }
                        },
                        scales: { y: { grid: { color: '#e2e8f0' }, ticks: { color: '#475569' } }, x: { ticks: { color: '#475569' } } }
                    }
                });
            });
        </script>
    @endpush
</div>