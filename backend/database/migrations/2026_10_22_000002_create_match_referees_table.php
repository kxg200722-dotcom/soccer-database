<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_referees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('role', 16)->comment('chief/assistant/fourth');
            $table->string('name');
            $table->timestamps();

            $table->unique(['match_id', 'role', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_referees');
    }
};
