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
        Schema::table('early_warnings', function (Blueprint $table) {
            if (! Schema::hasColumn('early_warnings', 'target_object')) {
                $table->string('target_object', 255)->nullable()->after('rule_code')->comment('e.g. Pos 521211 - JTIF, Transaksi FRA-001');
            }
            if (! Schema::hasColumn('early_warnings', 'reason')) {
                $table->text('reason')->nullable()->after('message')->comment('Alasan explainable pemicu warning');
            }
            if (! Schema::hasColumn('early_warnings', 'first_triggered_at')) {
                $table->timestamp('first_triggered_at')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('early_warnings', 'submission_id')) {
                $table->foreignId('submission_id')->nullable()->after('budget_bucket_id')->constrained('submissions')->nullOnDelete();
            }
            if (! Schema::hasColumn('early_warnings', 'budget_version_id')) {
                $table->foreignId('budget_version_id')->nullable()->after('submission_id')->constrained('budget_versions')->nullOnDelete();
            }
            if (! Schema::hasColumn('early_warnings', 'context_metadata')) {
                $table->json('context_metadata')->nullable()->after('budget_version_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_warnings', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropForeign(['budget_version_id']);
            $table->dropColumn([
                'target_object',
                'reason',
                'first_triggered_at',
                'submission_id',
                'budget_version_id',
                'context_metadata',
            ]);
        });
    }
};
