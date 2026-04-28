<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attack_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_type');      // e.g., 'BRUTE_FORCE', 'CREDENTIAL_STUFFING'
            $table->string('from_route');     // e.g., '/login', '/admin'
            $table->string('to_type');
            $table->string('to_route');
            $table->unsignedInteger('weight')->default(1);
            $table->float('probability')->default(0);
            $table->timestamps();

            $table->unique(['from_type', 'from_route', 'to_type', 'to_route'], 'transition_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attack_transitions');
    }
};
