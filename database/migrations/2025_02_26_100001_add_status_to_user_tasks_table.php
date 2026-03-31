<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('user_tasks', 'status')) {
                $table->unsignedTinyInteger('status')->default(0)->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('user_tasks', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
