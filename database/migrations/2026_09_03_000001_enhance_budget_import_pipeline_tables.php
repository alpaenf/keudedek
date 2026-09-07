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
        // Add revision & metadata columns to import_histories
        Schema::table('import_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('import_histories', 'budget_version_id')) {
                $table->foreignId('budget_version_id')->nullable()->after('user_id')->constrained('budget_versions')->nullOnDelete();
            }
            if (! Schema::hasColumn('import_histories', 'revision_no')) {
                $table->string('revision_no', 50)->nullable()->after('budget_version_id');
            }
            if (! Schema::hasColumn('import_histories', 'version_label')) {
                $table->string('version_label', 150)->nullable()->after('revision_no');
            }
            if (! Schema::hasColumn('import_histories', 'validation_report')) {
                $table->json('validation_report')->nullable()->after('invalid_rows');
            }
        });

        // Enhance budget_import_stagings with full hierarchy & sequence & validation details
        Schema::table('budget_import_stagings', function (Blueprint $table) {
            if (! Schema::hasColumn('budget_import_stagings', 'row_number')) {
                $table->unsignedInteger('row_number')->nullable()->after('import_history_id');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'rba_sequence_no')) {
                $table->string('rba_sequence_no', 50)->nullable()->after('row_number');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('department_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'program_code')) {
                $table->string('program_code', 50)->nullable()->after('funding_source_code');
                $table->string('program_name', 255)->nullable()->after('program_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'activity_code')) {
                $table->string('activity_code', 50)->nullable()->after('program_name');
                $table->string('activity_name', 255)->nullable()->after('activity_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'kro_code')) {
                $table->string('kro_code', 50)->nullable()->after('activity_name');
                $table->string('kro_name', 255)->nullable()->after('kro_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'ro_code')) {
                $table->string('ro_code', 50)->nullable()->after('kro_name');
                $table->string('ro_name', 255)->nullable()->after('ro_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'component_code')) {
                $table->string('component_code', 50)->nullable()->after('ro_name');
                $table->string('component_name', 255)->nullable()->after('component_code');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'subcomponent_code')) {
                $table->string('subcomponent_code', 50)->nullable()->after('component_name');
                $table->string('subcomponent_name', 255)->nullable()->after('subcomponent_code');
                $table->string('subcomponent_full_code', 100)->nullable()->after('subcomponent_name');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'description')) {
                $table->text('description')->nullable()->after('account_name');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'volume')) {
                $table->decimal('volume', 12, 2)->default(1.00)->after('description');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'unit')) {
                $table->string('unit', 50)->default('Kegiatan')->after('volume');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0.00)->after('unit');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'validation_errors')) {
                $table->json('validation_errors')->nullable()->after('error_message');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'is_duplicate')) {
                $table->boolean('is_duplicate')->default(false)->after('validation_errors');
            }
            if (! Schema::hasColumn('budget_import_stagings', 'matched_bucket_id')) {
                $table->foreignId('matched_bucket_id')->nullable()->after('is_duplicate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_import_stagings', function (Blueprint $table) {
            $table->dropColumn([
                'row_number',
                'rba_sequence_no',
                'department_id',
                'program_code',
                'program_name',
                'activity_code',
                'activity_name',
                'kro_code',
                'kro_name',
                'ro_code',
                'ro_name',
                'component_code',
                'component_name',
                'subcomponent_code',
                'subcomponent_name',
                'subcomponent_full_code',
                'description',
                'volume',
                'unit',
                'unit_price',
                'validation_errors',
                'is_duplicate',
                'matched_bucket_id',
            ]);
        });

        Schema::table('import_histories', function (Blueprint $table) {
            $table->dropForeign(['budget_version_id']);
            $table->dropColumn([
                'budget_version_id',
                'revision_no',
                'version_label',
                'validation_report',
            ]);
        });
    }
};
