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
        Schema::table('submission_import_stagings', function (Blueprint $table) {
            if (! Schema::hasColumn('submission_import_stagings', 'evidence_number')) {
                $table->string('evidence_number', 100)->nullable()->after('row_number');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('evidence_number');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'study_program_id')) {
                $table->foreignId('study_program_id')->nullable()->after('department_code');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'study_program_code')) {
                $table->string('study_program_code', 50)->nullable()->after('study_program_id');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'subcomponent_code')) {
                $table->string('subcomponent_code', 50)->nullable()->after('account_code');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'budget_control_key')) {
                $table->string('budget_control_key', 100)->nullable()->after('subcomponent_code');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'matched_bucket_id')) {
                $table->foreignId('matched_bucket_id')->nullable()->after('budget_control_key');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'matched_hierarchy')) {
                $table->json('matched_hierarchy')->nullable()->after('matched_bucket_id');
            }
            if (! Schema::hasColumn('submission_import_stagings', 'duplicate_status')) {
                $table->string('duplicate_status', 30)->default('NONE')->after('validation_status'); // NONE, DUPLICATE_IN_FILE, DUPLICATE_IN_DB
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_import_stagings', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_number',
                'transaction_date',
                'study_program_id',
                'study_program_code',
                'subcomponent_code',
                'budget_control_key',
                'matched_bucket_id',
                'matched_hierarchy',
                'duplicate_status',
            ]);
        });
    }
};
