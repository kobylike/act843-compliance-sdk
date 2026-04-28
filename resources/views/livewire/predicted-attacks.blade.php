<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-10">
    <h3 class="font-semibold text-slate-800 flex items-center gap-2">🎯 Predicted Next Attack</h3>
    @if(count($predictions) > 0)
        <ul class="mt-3 space-y-2">
            @foreach($predictions as $pred)
                <li class="flex justify-between text-sm">
                    <span><span class="font-mono">{{ $pred['type'] }}</span> on <span
                            class="font-mono">{{ $pred['route'] }}</span></span>
                    <span class="text-indigo-600 font-bold">{{ $pred['probability'] }}%</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-slate-500 text-sm mt-3">No prediction yet – attack patterns are still forming.</p>
    @endif
</div>