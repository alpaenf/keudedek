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
        // 1. Enhance submissions table for provenance & RBC-08 / RBC-07
        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'original_budget_bucket_id')) {
                $table->foreignId('original_budget_bucket_id')->nullable()->after('budget_bucket_id')->constrained('budget_buckets')->nullOnDelete();
            }
            if (! Schema::hasColumn('submissions', 'is_documented_historical')) {
                $table->boolean('is_documented_historical')->default(false)->after('amount');
            }
            if (! Schema::hasColumn('submissions', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('status');
            }
            if (! Schema::hasColumn('submissions', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('notes');
                $table->index('idempotency_key', 'submissions_idempotency_key_idx');
            }
        });

        // Initialize original_budget_bucket_id for existing records
        DB::statement('UPDATE submissions SET original_budget_bucket_id = budget_bucket_id WHERE original_budget_bucket_id IS NULL AND budget_bucket_id IS NOT NULL');

        // 2. Enhance budget_buckets table for RBC-08 & RBC-09 flags
        Schema::table('budget_buckets', function (Blueprint $table) {
            if (! Schema::hasColumn('budget_buckets', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('available_balance');
            }
            if (! Schema::hasColumn('budget_buckets', 'data_incomplete')) {
                $table->boolean('data_incomplete')->default(false)->after('needs_review');
            }
        });

        // 3. Create rbc_event_logs table for RBC-07 Idempotency
        if (! Schema::hasTable('rbc_event_logs')) {
            Schema::create('rbc_event_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 100)->unique();
                $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
                $table->string('operation_type', 50);
                $table->string('payload_hash', 64);
                $table->json('response_data')->nullable();
                $table->timestamps();

                $table->index(['event_id', 'operation_type'], 'rbc_event_op_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rbc_event_logs');

        Schema::table('budget_buckets', function (Blueprint $table) {
            if (Schema::hasColumn('budget_buckets', 'data_incomplete')) {
                $table->dropColumn('data_incomplete');
            }
            if (Schema::hasColumn('budget_buckets', 'needs_review')) {
                $table->dropColumn('needs_review');
            }
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'idempotency_key')) {
                $table->dropIndex('submissions_idempotency_key_idx');
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('submissions', 'needs_review')) {
                $table->dropColumn('needs_review');
            }
            if (Schema::hasColumn('submissions', 'is_documented_historical')) {
                $table->dropColumn('is_documented_historical');
            }
            if (Schema::hasColumn('submissions', 'original_budget_bucket_id')) {
                $table->dropForeign(['original_budget_bucket_id']);
                $table->dropColumn('original_budget_bucket_id');
            }
        });
    }
};
