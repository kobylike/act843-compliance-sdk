<?php

namespace GhanaCompliance\Act843SDK\Models;

use Illuminate\Database\Eloquent\Model;

class AnomalyTrainingData extends Model
{
    protected $fillable = ['hour', 'day_of_week', 'user_agent_hash', 'ip_class', 'request_rate', 'is_anomaly'];
}
