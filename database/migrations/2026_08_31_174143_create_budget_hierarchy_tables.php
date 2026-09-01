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
        Schema::create('budget_programs', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_activities', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('parent_program_code')->nullable()->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_kros', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('parent_activity_code')->nullable()->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_ros', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('parent_kro_code')->nullable()->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_components', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('parent_ro_code')->nullable()->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_subcomponents', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->default(2026);
            $table->string('code')->index();
            $table->string('full_code')->nullable()->index();
            $table->string('parent_component_code')->nullable()->index();
            $table->string('name');
            $table->string('header_color')->nullable()->default('green');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::create('budget_subaccounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('parent_account_code')->index();
            $table->string('name');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });

        Schema::table('budget_buckets', function (Blueprint $table) {
            if (! Schema::hasColumn('budget_buckets', 'subcomponent_full_code')) {
                $table->string('subcomponent_full_code')->nullable()->after('department_id')->index();
            }
            if (! Schema::hasColumn('budget_buckets', 'subcomponent_name')) {
                $table->string('subcomponent_name')->nullable()->after('subcomponent_full_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_buckets', function (Blueprint $table) {
            $table->dropColumn(['subcomponent_full_code', 'subcomponent_name']);
        });

        Schema::dropIfExists('budget_subaccounts');
        Schema::dropIfExists('budget_accounts');
        Schema::dropIfExists('budget_subcomponents');
        Schema::dropIfExists('budget_components');
        Schema::dropIfExists('budget_ros');
        Schema::dropIfExists('budget_kros');
        Schema::dropIfExists('budget_activities');
        Schema::dropIfExists('budget_programs');
    }
};
