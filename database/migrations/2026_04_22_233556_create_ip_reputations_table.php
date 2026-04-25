<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_reputations', function (Blueprint $table) {
            $table->id();

            // IP address (unique)
            $table->string('ip')->unique();

            // Geographic & network enrichment
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('isp')->nullable();

            // Reputation scoring
            $table->integer('score')->default(0);
            $table->integer('failures')->default(0);          // Recent failures (decaying)
            $table->integer('total_failures')->default(0);    // Lifetime failures

            // Risk level classification
            $table->string('risk_level')->default('LOW');     // LOW, MEDIUM, HIGH

            // Blocking fields (present but NEVER used – always false/null)
            $table->boolean('blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->string('block_reason')->nullable();

            // Activity tracking
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_activity')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('score');
            $table->index('risk_level');
            $table->index('last_seen');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_reputations');
    }
};
