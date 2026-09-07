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
        Schema::table('budget_buckets', function (Blueprint $table) {
            // Add normal index for foreign key first
            $table->index('fiscal_year_id', 'budget_buckets_fiscal_year_id_index');
            // Now drop the unique index
            $table->dropUnique('bucket_fiscal_dept_code_unique');
            // Add the version-aware unique index
            $table->unique(['fiscal_year_id', 'budget_version_id', 'department_id', 'account_code', 'subcomponent_full_code'], 'bucket_version_dept_acc_subcomp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_buckets', function (Blueprint $table) {
            $table->dropUnique('bucket_version_dept_acc_subcomp_unique');
            $table->unique(['fiscal_year_id', 'department_id', 'account_code'], 'bucket_fiscal_dept_code_unique');
            $table->dropIndex('budget_buckets_fiscal_year_id_index');
        });
    }
};
