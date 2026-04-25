<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAlert extends Model
{
    protected $fillable = [
        'severity',
        'title',
        'message',
        'context',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
        'resolved_by',
    ];

    protected $casts = [
        'context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'HIGH');
    }
}
