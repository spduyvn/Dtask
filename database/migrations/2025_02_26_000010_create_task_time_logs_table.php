<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('minutes')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('user_id');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
    }
};
