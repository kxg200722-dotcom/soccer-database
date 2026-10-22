<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('group_type', 32)->comment('university/middle_school/primary_school');
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->string('home_team_logo')->nullable();
            $table->string('away_team_logo')->nullable();
            $table->dateTime('match_time');
            $table->string('venue')->nullable();
            $table->string('status', 16)->default('not_started')->comment('not_started/live/finished');
            $table->unsignedSmallInteger('home_score')->default(0);
            $table->unsignedSmallInteger('away_score')->default(0);
            $table->json('lineup_home')->nullable();
            $table->json('lineup_away')->nullable();
            $table->string('live_link', 512)->nullable();
            $table->unsignedInteger('version')->default(0)->comment('乐观锁版本号');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_type', 'status', 'match_time']);
            $table->index(['status', 'match_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
