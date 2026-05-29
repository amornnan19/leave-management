<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: SQLite ALTER TABLE does not support foreign key constraints on added columns.
     * department_id and manager_id use index() instead of constrained() to stay SQLite-compatible.
     * Application-level integrity is enforced via Eloquent relations and seeders.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee');
            $table->foreignId('department_id')->nullable()->index();
            $table->foreignId('manager_id')->nullable()->index();
            $table->string('employee_code')->nullable()->unique();
            $table->date('joined_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropIndex(['manager_id']);
            $table->dropUnique(['employee_code']);
            $table->dropColumn(['role', 'department_id', 'manager_id', 'employee_code', 'joined_at']);
        });
    }
};
