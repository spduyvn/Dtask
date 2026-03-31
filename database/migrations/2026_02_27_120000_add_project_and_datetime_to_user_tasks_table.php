<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('user_tasks', 'project_id')) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('projects')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('user_tasks', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('user_tasks', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('due_date');
            }
        });

        Schema::table('user_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('user_tasks', 'due_at')) {
                return;
            }
            $table->index(['user_id', 'due_at']);
            $table->index(['project_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('user_tasks', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
            if (Schema::hasColumn('user_tasks', 'start_at')) {
                $table->dropColumn('start_at');
            }
            if (Schema::hasColumn('user_tasks', 'due_at')) {
                $table->dropColumn('due_at');
            }
        });
    }
};

