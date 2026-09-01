<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles master table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Multi-role pivot table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // 3. Study Programs (Prodi under Jurusan)
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Add study_program_id to users and submissions
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('study_program_id')->nullable()->after('department_id')->constrained('study_programs')->nullOnDelete();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('study_program_id')->nullable()->after('department_id')->constrained('study_programs')->nullOnDelete();
            $table->string('evidence_number', 100)->nullable()->after('submission_number');
            $table->date('transaction_date')->nullable()->after('evidence_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['study_program_id']);
            $table->dropColumn(['study_program_id', 'evidence_number', 'transaction_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['study_program_id']);
            $table->dropColumn(['study_program_id']);
        });

        Schema::dropIfExists('study_programs');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
