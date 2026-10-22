<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('type', 16)->comment('goal/yellow/red/substitution/text');
            $table->unsignedTinyInteger('minute')->comment('比赛分钟');
            $table->string('team_side', 8)->nullable()->comment('home/away');
            $table->string('player_name')->nullable();
            $table->string('player_no', 16)->nullable();
            $table->string('related_player_name')->nullable()->comment('助攻/换上球员');
            $table->string('content', 500)->nullable();
            $table->string('client_token', 64)->unique()->comment('前端防重复提交令牌');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'type']);
            $table->index(['match_id', 'minute', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
