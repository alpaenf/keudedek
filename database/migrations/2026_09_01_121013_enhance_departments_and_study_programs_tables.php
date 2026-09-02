<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('type', 20)->default('DEPARTMENT')->after('name'); // FACULTY, DEPARTMENT
            $table->string('official_code', 50)->nullable()->after('code');
            $table->string('status', 20)->default('ACTIVE')->after('is_active'); // ACTIVE, INACTIVE, ARCHIVED
            $table->string('source_type', 30)->default('INTERNAL')->after('status'); // OFFICIAL_IMPORT, OFFICIAL_DOCUMENT, INTERNAL, NEEDS_VALIDATION
            $table->unsignedSmallInteger('effective_year')->nullable()->default(2026)->after('source_type');
        });

        Schema::table('study_programs', function (Blueprint $table) {
            $table->string('official_code', 50)->nullable()->after('code');
            $table->string('status', 20)->default('ACTIVE')->after('is_active'); // ACTIVE, INACTIVE, ARCHIVED
            $table->string('source_type', 30)->default('INTERNAL')->after('status'); // OFFICIAL_IMPORT, OFFICIAL_DOCUMENT, INTERNAL, NEEDS_VALIDATION
            $table->unsignedSmallInteger('effective_year')->nullable()->default(2026)->after('source_type');
        });

        // Set existing records types
        DB::table('departments')->where('code', 'like', '%FT%')->update(['type' => 'FACULTY']);
        DB::table('departments')->where('code', 'not like', '%FT%')->update(['type' => 'DEPARTMENT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->dropColumn(['official_code', 'status', 'source_type', 'effective_year']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['type', 'official_code', 'status', 'source_type', 'effective_year']);
        });
    }
};
