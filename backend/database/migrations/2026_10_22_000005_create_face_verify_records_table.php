<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_verify_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('player_name');
            $table->string('player_no', 16)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('result', 16)->comment('pass/fail/pending');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('channel', 32)->default('mock');
            $table->string('request_id')->nullable();
            $table->string('remark', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_verify_records');
    }
};
