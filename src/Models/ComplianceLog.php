<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceLog extends Model
{
    protected $fillable = [
        'type',
        'ip_address',
        'score',
        'severity', // ✅ FIXED
        'attempts',
        'meta',
        'recommendation',
    ];

    protected $casts = [
        'meta' => 'array'
    ];
}
