<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class ThreatIntelCache extends Model
{
    protected $fillable = ['ip', 'abuse_score', 'total_reports', 'last_reported_at'];
    protected $casts = ['last_reported_at' => 'datetime'];
}
