<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 32)->nullable();
            $table->timestamps();
        });

        // Seed default types (was previously hardcoded as 0 = Personal, 1 = Midota)
        DB::table('project_types')->insert([
            ['name' => 'Personal', 'color' => '#22c55e', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Midota', 'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('project_types');
    }
};
