<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('remediations', function (Blueprint $table) {
            $table->id();
            $table->string('finding');      // e.g., 'weak password policy'
            $table->text('action_taken');   // e.g., 'Set PASSWORD_MIN_LENGTH=12'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('resolved_at');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('remediations');
    }
};
