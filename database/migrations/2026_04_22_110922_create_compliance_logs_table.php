<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_logs', function (Blueprint $table) {
            $table->id();

            $table->string('type');
            $table->string('ip_address')->index();

            $table->integer('score')->default(0);
            $table->string('severity')->default('LOW');

            $table->integer('attempts')->default(0);

            // future AI layer
            $table->json('meta')->nullable();

            $table->string('recommendation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_logs');
    }
};
