<div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100"
    wire:poll.10s="{{ $autoRefresh ? 'loadStats' : '' }}">
    <div class="container mx-auto px-6 py-8">

        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1
                    class="text-4xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    🛡️ Security & Compliance Intelligence
                </h1>
                <p class="text-slate-500 mt-2 text-sm">Real-time threat monitoring & intelligence dashboard</p>
            </div>
            <div class="flex gap-3">
                <button wire:click="exportCsv"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
                    📥 Export CSV
                </button>
                <a href="{{ route('compliance.report') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
                    📄 PDF Report
                </a>
                <button wire:click="$toggle('autoRefresh')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
                    {{ $autoRefresh ? '⏸️ Pause' : '▶️ Auto-refresh' }}
                </button>
            </div>
        </div>

        <!-- Alerts Ticker -->
        @if($alerts->count() > 0)
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm animate-pulse">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🚨</span>
                    <span class="font-semibold text-red-700">{{ $alerts->count() }} Active Security Alerts</span>
                    <div class="flex-1 overflow-hidden ml-4">
                        <div class="whitespace-nowrap animate-marquee text-slate-600 text-sm">
                            @foreach($alerts as $alert)
                                <span class="inline-block mx-4">{{ $alert->title }} –
                                    {{ $alert->created_at->diffForHumans() }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div
            class="bg-white/70 backdrop-blur-sm rounded-2xl p-4 mb-8 flex flex-wrap gap-3 shadow-sm border border-slate-200">
            <select wire:model.live="dateRange"
                class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="today">📅 Today</option>
                <option value="week">📆 Last 7 Days</option>
                <option value="month">🗓️ Last 30 Days</option>
            </select>

            <select wire:model.live="filterSeverity"
                class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="">All Severities</option>
                <option value="HIGH">🔴 High</option>
                <option value="MEDIUM">🟡 Medium</option>
                <option value="LOW">🟢 Low</option>
            </select>

            <select wire:model.live="filterType"
                class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="">All Types</option>
                <option value="BRUTE_FORCE">Brute Force</option>
                <option value="UNAUTHORIZED_ACCESS">Unauthorized</option>
                <option value="CREDENTIAL_STUFFING">Credential Stuffing</option>
            </select>

            <button wire:click="loadStats"
                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-2 text-sm font-medium shadow-sm transition">
                🔄 Refresh
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-5 mb-10">
            @php
                $cards = [
                    ['label' => 'Total Threats', 'value' => $stats['total_threats'], 'color' => 'text-red-600'],
                    ['label' => 'High Risk', 'value' => $stats['high_risk'], 'color' => 'text-rose-600'],
                    ['label' => 'Medium Risk', 'value' => $stats['medium_risk'], 'color' => 'text-amber-600'],
                    ['label' => 'Low Risk', 'value' => $stats['low_risk'], 'color' => 'text-emerald-600'],
                    ['label' => 'Unique IPs', 'value' => $stats['unique_ips'], 'color' => 'text-blue-600'],
                    ['label' => 'Avg Score', 'value' => $stats['avg_score'], 'color' => 'text-purple-600'],
                    ['label' => 'Active Alerts', 'value' => $stats['active_alerts'], 'color' => 'text-red-600'],
                ];
            @endphp
            @foreach($cards as $card)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition">
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold {{ $card['color'] }} mt-2">{{ number_format($card['value']) }}</p>
                </div>
            @endforeach
        </div>

        <!-- Executive Summary with Functional "How to fix" -->
        <div class="mb-8 bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">📊 Compliance Snapshot</h3>
                <span
                    class="text-sm px-3 py-1 rounded-full
                    {{ $executiveSummary['status_color'] === 'green' ? 'bg-green-100 text-green-700' : ($executiveSummary['status_color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    Grade {{ $executiveSummary['grade'] }} – {{ $executiveSummary['status_text'] }}
                </span>
            </div>
            <div class="space-y-3">
                @foreach($executiveSummary['alerts'] as $alert)
                    <div
                        class="border-l-4 {{ $alert['severity'] === 'high' ? 'border-red-500 bg-red-50' : ($alert['severity'] === 'medium' ? 'border-yellow-500 bg-yellow-50' : 'border-green-500 bg-green-50') }} p-3 rounded-r-xl">
                        <p class="text-slate-800">{{ $alert['message'] }}</p>
                        @if($alert['action'])
                            <div class="mt-2">
                                <button wire:click="toggleFixModal" class="text-sm text-indigo-600 hover:underline">
                                    📌 {{ $alert['action_label'] }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Livewire Modal (no Alpine) -->
        @if($showFixModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div
                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                            <h3 class="text-lg font-bold text-white">How to Fix Weak Password Hashes</h3>
                        </div>
                        <div class="bg-white px-6 py-4">
                            <p class="text-slate-700 text-sm mb-4">The system has detected that some user passwords are
                                stored in a weak format (plain text, MD5, SHA1, or unknown).</p>
                            <div class="space-y-3">
                                <div class="bg-slate-50 p-3 rounded-lg">
                                    <p class="font-medium text-slate-800 mb-1">1. Run a deep password scan</p>
                                    <code
                                        class="text-xs bg-slate-800 text-slate-100 px-2 py-1 rounded block">php artisan compliance:scan-passwords --deep --force</code>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg">
                                    <p class="font-medium text-slate-800 mb-1">2. Review the output to see how many weak
                                        hashes exist</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg">
                                    <p class="font-medium text-slate-800 mb-1">3. Use your user management system to
                                        identify accounts with weak hashes (e.g., check password column format)</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg">
                                    <p class="font-medium text-slate-800 mb-1">4. Reset affected passwords or re‑hash them
                                        using bcrypt</p>
                                    <code
                                        class="text-xs bg-slate-800 text-slate-100 px-2 py-1 rounded block">User::where(...)->update(['password' => Hash::make('newpassword')]);</code>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-4">This system is detection‑only – it does not automatically
                                fix passwords. The steps above assist you in manual remediation.</p>
                        </div>
                        <div class="bg-slate-50 px-6 py-3 flex justify-end">
                            <button wire:click="toggleFixModal"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Rest of the dashboard unchanged -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-10">
            <!-- ... Compliance Health cards, Decision Engine, Attack Graph, Charts, Table, Top IPs ... -->
            <!-- (Keep exactly as before – no changes needed) -->
        </div>

        <!-- ... rest of your existing content ... -->
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:init', function () {
                let trendChart, attackChart, complianceTrendChart;

                function initCharts() {
                    const trendCtx = document.getElementById('trendChart')?.getContext('2d');
                    const attackCtx = document.getElementById('attackChart')?.getContext('2d');
                    const complianceCtx = document.getElementById('complianceTrendChart')?.getContext('2d');

                    if (trendCtx && !trendChart) {
                        const chartLabels = @json($chartData->pluck('label'));
                        const chartScores = @json($chartData->pluck('score'));
                        trendChart = new Chart(trendCtx, {
                            type: 'line',
                            data: { labels: chartLabels, datasets: [{ label: 'Avg Risk Score', data: chartScores, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.05)', borderWidth: 2, tension: 0.4, fill: true }] },
                            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#1e293b' } } } }
                        });
                    }

                    if (attackCtx && !attackChart) {
                        attackChart = new Chart(attackCtx, {
                            type: 'doughnut',
                            data: { labels: @json($attackTypes->pluck('type')), datasets: [{ data: @json($attackTypes->pluck('total')), backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'], borderWidth: 0 }] },
                            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#1e293b' } } } }
                        });
                    }

                    if (complianceCtx && !complianceTrendChart) {
                        const complianceLabels = @json(array_keys($complianceTrend));
                        const complianceScores = @json(array_values($complianceTrend));
                        complianceTrendChart = new Chart(complianceCtx, {
                            type: 'line',
                            data: { labels: complianceLabels, datasets: [{ label: 'Compliance Score', data: complianceScores, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)', borderWidth: 2, tension: 0.3, fill: true }] },
                            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#1e293b' } } } }
                        });
                    }
                }

                initCharts();
                Livewire.on('refreshDashboard', () => {
                    if (trendChart) trendChart.destroy();
                    if (attackChart) attackChart.destroy();
                    if (complianceTrendChart) complianceTrendChart.destroy();
                    trendChart = attackChart = complianceTrendChart = null;
                    initCharts();
                });
            });
        </script>
    @endpush

    <style>
        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .animate-marquee {
            animation: marquee 20s linear infinite;
            white-space: nowrap;
        }
    </style>
</div>