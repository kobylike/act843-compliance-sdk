<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            🕸️ Attack Graph (Interactive)
        </h3>
        <div class="flex gap-2">
            <select wire:model.live="timeRange" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1 text-sm">
                <option value="today">Today</option>
                <option value="week">Last 7 days</option>
                <option value="month">Last 30 days</option>
            </select>
        </div>
    </div>
    <div id="attackGraph" style="height: 500px; width: 100%; border: 1px solid #e2e8f0; border-radius: 1rem; background: #f8fafc;"></div>
    <p class="text-xs text-slate-400 mt-2 text-center">Nodes: IP addresses (size = risk score) | Attack types (orange). Hover for details.</p>

    @push('scripts')
    <script src="https://unpkg.com/vis-network@9.1.6/dist/vis-network.min.js"></script>
    <script>
        document.addEventListener('livewire:init', function () {
            let network = null;

            function initGraph() {
                const container = document.getElementById('attackGraph');
                const nodesData = @json($graphData['nodes']);
                const edgesData = @json($graphData['edges']);

                if (!nodesData.length) {
                    container.innerHTML = '<div class="flex items-center justify-center h-full text-slate-400">No attack data in selected period.</div>';
                    return;
                }

                const nodes = new vis.DataSet(nodesData.map(node => ({
                    id: node.id,
                    label: node.label,
                    group: node.group,
                    title: node.title,
                    size: node.size || 20,
                })));

                const edges = new vis.DataSet(edgesData.map(edge => ({
                    from: edge.from,
                    to: edge.to,
                    value: edge.value,
                    title: edge.title,
                    arrows: 'to',
                    color: { color: '#94a3b8', highlight: '#4f46e5' },
                })));

                const data = { nodes, edges };

                const options = {
                    nodes: {
                        shape: 'dot',
                        scaling: {
                            min: 10,
                            max: 40,
                            label: { enabled: true, min: 14, max: 30 },
                        },
                        font: { size: 12, face: 'Inter' },
                        shadow: true,
                    },
                    edges: {
                        smooth: { type: 'curvedCW', roundness: 0.2 },
                        width: 2,
                        font: { size: 10, align: 'middle' },
                    },
                    groups: {
                        ip: { color: { background: '#3b82f6', border: '#2563eb' }, shape: 'dot' },
                        attack: { color: { background: '#f97316', border: '#ea580c' }, shape: 'box' },
                    },
                    physics: {
                        enabled: true,
                        stabilization: true,
                        barnesHut: { gravitationalConstant: -8000, springConstant: 0.04 },
                    },
                    interaction: {
                        hover: true,
                        tooltipDelay: 200,
                        zoomView: true,
                        dragView: true,
                    },
                    layout: { improvedLayout: true },
                };

                network = new vis.Network(container, data, options);
            }

            initGraph();

            Livewire.on('refreshDashboard', () => {
                if (network) network.destroy();
                initGraph();
            });
        });
    </script>
    @endpush
</div>