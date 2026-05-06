<div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100"
    wire:poll.10s="{{ $autoRefresh ? 'loadStats' : '' }}">

    <!-- Toast Notifications Container (Alpine) -->
    <div class="fixed top-5 right-5 z-50 space-y-2" x-data="toast()" x-init="init()"
        @toast.window="addToast($event.detail)">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="bg-white rounded-xl shadow-lg border-l-4 w-80 mb-2 overflow-hidden" :class="{
                     'border-green-500': toast.type === 'success',
                     'border-red-500': toast.type === 'error',
                     'border-blue-500': toast.type === 'info',
                     'border-amber-500': toast.type === 'warning'
                 }">
                <div class="flex items-center p-4 gap-3">
                    <div x-show="toast.type === 'success'" class="text-green-500">✅</div>
                    <div x-show="toast.type === 'error'" class="text-red-500">❌</div>
                    <div x-show="toast.type === 'info'" class="text-blue-500">ℹ️</div>
                    <div x-show="toast.type === 'warning'" class="text-amber-500">⚠️</div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800" x-text="toast.message"></p>
                    </div>
                    <button @click="removeToast(toast.id)"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div class="h-1 w-full bg-slate-100">
                    <div class="h-full animate-progress" :class="{
                        'bg-green-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'error',
                        'bg-blue-500': toast.type === 'info',
                        'bg-amber-500': toast.type === 'warning'
                    }"></div>
                </div>
            </div>
        </template>
    </div>

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
                    class="cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
                    📥 Export CSV
                </button>
                <a href="{{ route('compliance.report') }}"
                    class="cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
                    📄 PDF Report
                </a>
                <button wire:click="$toggle('autoRefresh')"
                    class="cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-700 hover:bg-slate-50 transition">
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
                class="cursor-pointer bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="today">📅 Today</option>
                <option value="week">📆 Last 7 Days</option>
                <option value="month">🗓️ Last 30 Days</option>
            </select>

            <select wire:model.live="filterSeverity"
                class="cursor-pointer bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="">All Severities</option>
                <option value="HIGH">🔴 High</option>
                <option value="MEDIUM">🟡 Medium</option>
                <option value="LOW">🟢 Low</option>
            </select>

            <select wire:model.live="filterType"
                class="cursor-pointer bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm">
                <option value="">All Types</option>
                <option value="BRUTE_FORCE">Brute Force</option>
                <option value="UNAUTHORIZED_ACCESS">Unauthorized</option>
                <option value="CREDENTIAL_STUFFING">Credential Stuffing</option>
            </select>

            <button wire:click="loadStats"
                class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-2 text-sm font-medium shadow-sm transition">
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

        <!-- Compliance Health Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">📋 Compliance Health (Act 843)</h3>
                <div class="flex gap-3">
                    <button wire:click="runComplianceScans"
                        class="cursor-pointer px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-sm hover:bg-indigo-200 transition">
                        🔄 Run Checks Now
                    </button>
                    @if(config('compliance.allow_deep_password_scan', false))
                        <button wire:click="runDeepScan"
                            class="cursor-pointer px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-sm hover:bg-purple-200 transition">
                            🧠 Deep Password Audit
                        </button>
                    @endif
                    <span
                        class="text-sm px-3 py-1 rounded-full {{ $complianceHealth['score'] >= 80 ? 'bg-green-100 text-green-700' : ($complianceHealth['score'] >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                        Score: {{ $complianceHealth['score'] }} / 100 (Grade {{ $complianceHealth['grade'] }})
                    </span>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Password Policy card -->
                <div class="border border-slate-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl">🔐</span>
                        <h4 class="font-medium text-slate-800">Password Policy</h4>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Status</span><span
                                class="font-medium">{{ $complianceHealth['password_policy']['status'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Weak
                                Hashes</span><span>{{ $complianceHealth['password_policy']['weak_hashes'] }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-slate-500">Missing
                                Policies</span><span>{{ $complianceHealth['password_policy']['weak_policies'] }}</span>
                        </div>
                        @if($complianceHealth['last_checks']['password'])
                            <div class="flex justify-between text-xs text-slate-400">Last scan:
                                {{ $complianceHealth['last_checks']['password']->diffForHumans() }}</div>
                        @endif
                    </div>
                </div>
                <!-- Data Retention card -->
                <div class="border border-slate-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl">🗄️</span>
                        <h4 class="font-medium text-slate-800">Data Retention</h4>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Status</span><span
                                class="font-medium">{{ $complianceHealth['data_retention']['status'] }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Non‑compliant
                                tables</span><span>{{ $complianceHealth['data_retention']['non_compliant'] }}</span>
                        </div>
                        @if($complianceHealth['last_checks']['retention'])
                            <div class="flex justify-between text-xs text-slate-400">Last scan:
                                {{ $complianceHealth['last_checks']['retention']->diffForHumans() }}</div>
                        @endif
                        <div class="flex gap-2 mt-3">
                            <button wire:click="previewPurge"
                                class="cursor-pointer text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1 rounded-md transition">🔍
                                Preview old data</button>
                            <button wire:click="runPurge"
                                wire:confirm="Are you sure you want to permanently delete old records?"
                                class="cursor-pointer text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded-md transition">🗑️
                                Delete old records now</button>
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($complianceHealth['recommendations']))
                <div class="mt-4 p-3 bg-amber-50 rounded-xl border border-amber-200">
                    <div class="flex items-center gap-2 text-amber-700 text-sm font-medium mb-1">⚡ Recommendations</div>
                    <ul class="text-xs text-amber-800 space-y-1">
                        @foreach($complianceHealth['recommendations'] as $rec)
                            <li>• {{ $rec }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Administrative Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-10">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2 mb-4">⚙️ Administrative Actions</h3>
            <div class="flex flex-wrap gap-3">
                <button wire:click="sendReportNow"
                    class="cursor-pointer px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm hover:bg-indigo-100 transition">
                    📤 Send report to regulator now
                </button>
                <button wire:click="sendWeeklyReportNow"
                    class="cursor-pointer px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm hover:bg-indigo-100 transition">
                    📧 Email weekly report now
                </button>
                <button wire:click="runRouteAudit"
                    class="cursor-pointer px-4 py-2 bg-amber-50 text-amber-700 rounded-lg text-sm hover:bg-amber-100 transition">
                    🔍 Scan routes for missing protection
                </button>
                <button wire:click="runDsrEvaluation"
                    class="cursor-pointer px-4 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm hover:bg-purple-100 transition">
                    📊 Run DSR evaluation (last 30 days)
                </button>
                <button wire:click="decayIpScores"
                    class="cursor-pointer px-4 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm hover:bg-blue-100 transition">
                    📉 Decay IP scores now
                </button>
                @if(config('compliance.anomaly_detection', false))
                    <button wire:click="trainAnomalyModel"
                        class="cursor-pointer px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg text-sm hover:bg-emerald-100 transition">
                        🤖 Train anomaly detection model
                    </button>
                @endif
            </div>
        </div>

        <!-- Decision Engine & Attack Graph -->
        @livewire('decision-engine')
        @livewire('attack-graph')
        @livewire('predicted-attacks')

        <!-- Charts Row -->
        <div class="grid lg:grid-cols-3 gap-8 mb-10">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">📈 Risk Score Trend</h3>
                <canvas id="trendChart" class="w-full h-64"></canvas>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">🎯 Attack Type Distribution</h3>
                <canvas id="attackChart" class="w-full h-64"></canvas>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">📊 Compliance Score Trend</h3>
                <canvas id="complianceTrendChart" class="w-full h-64"></canvas>
            </div>
        </div>

        <!-- Table & Top IPs -->
        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 overflow-x-auto">
                <h3 class="font-semibold text-slate-800 mb-4">🔍 Threat Investigation Table</h3>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-slate-600 border-b border-slate-200">
                            <th class="py-3 px-2">Type</th>
                            <th class="py-3 px-2">IP</th>
                            <th class="py-3 px-2">Score</th>
                            <th class="py-3 px-2">Severity</th>
                            <th class="py-3 px-2">Attempts</th>
                            <th class="py-3 px-2">Reason</th>
                            <th class="py-3 px-2">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                <td class="py-3 px-2 text-slate-700">{{ $log->type }}</td>
                                <td class="py-3 px-2"><a href="{{ route('compliance.ip.profile', $log->ip_address) }}"
                                        class="cursor-pointer text-indigo-600 hover:text-indigo-800 font-medium">{{ $log->ip_address }}</a>
                                </td>
                                <td class="py-3 px-2 font-mono font-bold">{{ $log->score }}</td>
                                <td class="py-3 px-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $log->severity === 'HIGH' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $log->severity === 'MEDIUM' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ $log->severity === 'LOW' ? 'bg-green-100 text-green-700' : '' }}">
                                        {{ $log->severity }}
                                    </span>
                                </td>
                                <td class="py-3 px-2">{{ $log->attempts }}</td>
                                <td class="py-3 px-2 text-slate-500 text-xs">
                                    <div title="{{ $log->meta['explanation'] ?? 'No explanation' }}"
                                        class="cursor-help max-w-xs truncate">
                                        {{ \Illuminate\Support\Str::limit($log->meta['explanation'] ?? 'N/A', 60) }}
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-slate-500 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-5">{{ $logs->links() }}</div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">🚨 Top Risky IPs</h3>
                <div class="space-y-3">
                    @foreach($ips as $ip)
                        <div
                            class="flex justify-between items-center p-3 bg-slate-50 rounded-xl hover:shadow-sm transition">
                            <div>
                                <a href="{{ route('compliance.ip.profile', $ip->ip) }}"
                                    class="cursor-pointer font-mono text-sm font-medium text-slate-800 hover:text-indigo-600">{{ $ip->ip }}</a>
                                @if($ip->country) <span class="text-xs text-slate-500 ml-2">{{ $ip->country }}</span> @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-24 bg-slate-200 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: {{ $ip->score }}%"></div>
                                </div>
                                <span class="text-sm font-bold text-slate-700">{{ $ip->score }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Livewire Modal (no Alpine) -->
    @if($showCommandModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white">{{ $modalTitle }}</h3>
                    </div>
                    <div class="bg-white px-6 py-4">
                        <div class="text-slate-700 text-sm max-h-96 overflow-y-auto font-mono">
                            {!! $modalContent !!}
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3 flex justify-end">
                        <button wire:click="closeCommandModal"
                            class="cursor-pointer px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            function toast() {
                return {
                    toasts: [],
                    init() {
                        this.toasts = [];
                    },
                    addToast({ message, type = 'success' }) {
                        const id = Date.now();
                        this.toasts.push({ id, message, type, show: true });
                        setTimeout(() => {
                            this.removeToast(id);
                        }, 5000);
                    },
                    removeToast(id) {
                        const index = this.toasts.findIndex(t => t.id === id);
                        if (index !== -1) this.toasts.splice(index, 1);
                    }
                }
            }

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

        @keyframes progress {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        .animate-marquee {
            animation: marquee 20s linear infinite;
            white-space: nowrap;
        }

        .animate-progress {
            animation: progress 5s linear forwards;
        }
    </style>
</div>