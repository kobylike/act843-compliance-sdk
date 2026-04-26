<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use Illuminate\Console\Command;
use GhanaCompliance\Act843SDK\Services\Security\IpReputationEngine;

class DecayIpReputations extends Command
{
    protected $signature = 'security:decay-ips';
    protected $description = 'Decay IP reputation scores for inactive IPs';

    public function handle(IpReputationEngine $engine)
    {
        $updated = $engine->decayAllScores();
        $this->info("Decayed {$updated} IP reputation records.");
    }
}
