<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_cards', function (Blueprint $table) {
            $table->foreignId('flash_card_group_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flash_cards', function (Blueprint $table) {
            $table->dropForeign(['flash_card_group_id']);
            $table->dropColumn('flash_card_group_id');
        });
    }
};
