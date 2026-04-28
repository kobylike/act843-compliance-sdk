<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-10">
    <h3 class="font-semibold text-slate-800 flex items-center gap-2 mb-4">
        ⚙️ Decision Engine (Proactive Fixes)
    </h3>

    @if(session()->has('decision_message'))
        @foreach(session('decision_message') as $msg)
            <div
                class="mb-3 p-2 rounded-lg text-sm {{ $msg['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                {{ $msg['text'] }}
            </div>
        @endforeach
    @endif

    @if(count($recommendations) > 0)
        <div class="space-y-3">
            @foreach($recommendations as $index => $rec)
                @php
                    $fixKey = match (true) {
                        str_contains($rec, 'minimum length') => 'min_length',
                        str_contains($rec, 'complexity') => 'complexity',
                        str_contains($rec, 'retention') => 'retention',
                        default => 'unknown'
                    };
                @endphp
                @if($fixKey !== 'unknown')
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                        <span class="text-sm">{{ $rec }}</span>
                        <button wire:click="applyFix('{{ $fixKey }}')"
                            class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700 transition">
                            Apply Fix
                        </button>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-slate-500 text-sm">No pending recommendations – system already compliant.</p>
    @endif
</div>