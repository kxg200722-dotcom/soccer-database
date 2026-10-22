<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('type', 32)->comment('sign_in/record_confirm/referee_report');
            $table->string('file_name');
            $table->string('file_path')->comment('私有 disk 相对路径');
            $table->string('mime', 64);
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['match_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_files');
    }
};
