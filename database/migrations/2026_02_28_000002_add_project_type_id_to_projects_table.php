<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_type_id')->nullable()->after('end_date')->constrained('project_types')->nullOnDelete();
        });

        // Migrate: type 0 -> project_type_id 1 (Personal), type 1 -> project_type_id 2 (Midota)
        DB::table('projects')->where('type', 0)->update(['project_type_id' => 1]);
        DB::table('projects')->where('type', 1)->update(['project_type_id' => 2]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(0)->after('end_date');
        });

        DB::table('projects')->where('project_type_id', 1)->update(['type' => 0]);
        DB::table('projects')->where('project_type_id', 2)->update(['type' => 1]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn('project_type_id');
        });
    }
};
