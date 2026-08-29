<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_buckets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->cascadeOnDelete();
            $table->string('account_code', 50);
            $table->string('account_name');
            $table->decimal('initial_budget', 15, 2)->default(0);
            $table->decimal('allocated_budget', 15, 2)->default(0);
            $table->decimal('reserved_budget', 15, 2)->default(0);
            $table->decimal('realized_budget', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'department_id', 'account_code'], 'bucket_fiscal_dept_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_buckets');
    }
};
