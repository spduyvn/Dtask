<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Project uses MySQL: enum values are defined on columns in project_members and tasks tables.
 * This migration is a no-op for MySQL (PostgreSQL would create custom types here).
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL: enum columns are defined in create_project_members_table and create_tasks_table
    }

    public function down(): void
    {
        // Nothing to drop for MySQL
    }
};
