<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class IpReputation extends Model
{
    protected $table = 'ip_reputations';

    protected $fillable = [
        'ip',
        'score',
        'failures',
        'total_failures',
        'risk_level',
        'last_seen',
        'last_activity',
        'country',
        'country_code',
        'isp',
        // ❌ 'blocked', 'blocked_at', 'block_reason' removed – not used
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'last_activity' => 'datetime',
    ];

    // Scopes remain
    public function scopeHighRisk($query)
    {
        return $query->where('score', '>=', 80);
    }

    public function scopeActive($query)
    {
        return $query->where('last_seen', '>', now()->subDays(7));
    }

    public function logs()
    {
        return $this->hasMany(ComplianceLog::class, 'ip_address', 'ip');
    }
}
