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
        // 1. Enhance rule_configs for versioning and core distinction
        Schema::table('rule_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('rule_configs', 'parameter_version')) {
                $table->integer('parameter_version')->default(1)->after('parameters');
            }
            if (! Schema::hasColumn('rule_configs', 'indicator_type')) {
                $table->string('indicator_type', 20)->default('CORE')->after('category'); // CORE, OPERATIONAL, EXCEPTION
            }
        });

        // 2. Enhance early_warnings for explainability and evaluation state
        Schema::table('early_warnings', function (Blueprint $table) {
            if (! Schema::hasColumn('early_warnings', 'operator')) {
                $table->string('operator', 10)->nullable()->after('threshold_value');
            }
            if (! Schema::hasColumn('early_warnings', 'parameter_version')) {
                $table->integer('parameter_version')->default(1)->after('rule_config_id');
            }
            if (! Schema::hasColumn('early_warnings', 'eval_status')) {
                $table->string('eval_status', 30)->default('TRIGGERED')->after('lifecycle_state'); // TRIGGERED, NOT_TRIGGERED, CANNOT_BE_EVALUATED
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_warnings', function (Blueprint $table) {
            if (Schema::hasColumn('early_warnings', 'eval_status')) {
                $table->dropColumn('eval_status');
            }
            if (Schema::hasColumn('early_warnings', 'parameter_version')) {
                $table->dropColumn('parameter_version');
            }
            if (Schema::hasColumn('early_warnings', 'operator')) {
                $table->dropColumn('operator');
            }
        });

        Schema::table('rule_configs', function (Blueprint $table) {
            if (Schema::hasColumn('rule_configs', 'indicator_type')) {
                $table->dropColumn('indicator_type');
            }
            if (Schema::hasColumn('rule_configs', 'parameter_version')) {
                $table->dropColumn('parameter_version');
            }
        });
    }
};
