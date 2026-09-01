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
        Schema::create('budget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->cascadeOnDelete();
            $table->string('revision_no', 50)->default('Rev 00'); // e.g. Rev 00, Rev 01, Rev 02
            $table->string('version_label', 150)->nullable(); // e.g. DIPA Awal 2026, Revisi 02 DIPA
            $table->string('status', 20)->default('DRAFT'); // DRAFT, ACTIVE, ARCHIVED
            $table->date('effective_at')->nullable();
            $table->string('source_reference', 255)->nullable(); // e.g. DIPA-139.03.2.693420/2026
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'funding_source_id', 'revision_no'], 'fy_fund_rev_unique');
        });

        Schema::table('budget_buckets', function (Blueprint $table) {
            $table->foreignId('budget_version_id')->nullable()->after('fiscal_year_id')->constrained('budget_versions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_buckets', function (Blueprint $table) {
            $table->dropForeign(['budget_version_id']);
            $table->dropColumn(['budget_version_id']);
        });

        Schema::dropIfExists('budget_versions');
    }
};
