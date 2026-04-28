<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use Livewire\Component;
use GhanaCompliance\Act843SDK\Services\Security\AttackGraphPredictor;
use Illuminate\Support\Facades\Cache;

class PredictedAttacks extends Component
{
    public $predictions = [];
    public $currentIP = null;

    public function mount()
    {
        $this->currentIP = request()->ip();
        $this->loadPredictions();
    }

    public function loadPredictions()
    {
        $lastAttack = Cache::get("last_attack_{$this->currentIP}");
        if ($lastAttack) {
            $this->predictions = app(AttackGraphPredictor::class)->predict(
                $lastAttack['type'],
                $lastAttack['route']
            );
        }
    }

    public function render()
    {
        return view('compliance::livewire.predicted-attacks');
    }
}
