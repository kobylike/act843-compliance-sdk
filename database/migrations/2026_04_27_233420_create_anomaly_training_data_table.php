<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anomaly_training_data', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('hour');
            $table->tinyInteger('day_of_week');
            $table->string('user_agent_hash', 64);
            $table->string('ip_class', 20);
            $table->float('request_rate');
            $table->boolean('is_anomaly')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_training_data');
    }
};
