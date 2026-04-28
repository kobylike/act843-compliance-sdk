<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AttackTransition extends Model
{
    protected $table = 'attack_transitions';

    protected $fillable = [
        'from_type',
        'from_route',
        'to_type',
        'to_route',
        'weight',
        'probability'
    ];

    /**
     * Update probability for all transitions from a given source.
     */
    public static function updateProbability($fromType, $fromRoute)
    {
        $totalWeight = self::where('from_type', $fromType)
            ->where('from_route', $fromRoute)
            ->sum('weight');

        if ($totalWeight > 0) {
            self::where('from_type', $fromType)
                ->where('from_route', $fromRoute)
                ->update([
                    'probability' => DB::raw('weight / ' . $totalWeight)
                ]);
        }
    }

    /**
     * Record a transition from one attack to another.
     */
    public static function recordTransition($fromType, $fromRoute, $toType, $toRoute)
    {
        $transition = self::firstOrCreate([
            'from_type' => $fromType,
            'from_route' => $fromRoute,
            'to_type' => $toType,
            'to_route' => $toRoute,
        ]);

        $transition->increment('weight');
        self::updateProbability($fromType, $fromRoute);
    }
}
