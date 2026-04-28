<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class Remediation extends Model
{
    protected $fillable = ['finding', 'action_taken', 'user_id', 'resolved_at'];
    protected $casts = ['resolved_at' => 'datetime'];
}
