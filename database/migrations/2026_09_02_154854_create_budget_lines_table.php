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
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();

            // 1. Versioning & Ownership Context
            $table->foreignId('budget_version_id')->constrained('budget_versions')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->nullable()->constrained('funding_sources')->nullOnDelete();

            // 2. Control Bucket Relation (Loose/Nullable so lines can exist prior to or independent of bucket aggregation)
            $table->foreignId('budget_bucket_id')->nullable()->constrained('budget_buckets')->nullOnDelete();

            // 3. RBA Sequence Number (Contextual uniqueness, NOT global primary key)
            $table->string('rba_sequence_no', 50)->comment('Contextual sequence in RBA, e.g. 001, 1, 1.1');

            // 4. Hierarchy Foreign Keys (Referencing Master Nomenklatur)
            $table->foreignId('budget_program_id')->nullable()->constrained('budget_programs')->nullOnDelete();
            $table->foreignId('budget_activity_id')->nullable()->constrained('budget_activities')->nullOnDelete();
            $table->foreignId('budget_kro_id')->nullable()->constrained('budget_kros')->nullOnDelete();
            $table->foreignId('budget_ro_id')->nullable()->constrained('budget_ros')->nullOnDelete();
            $table->foreignId('budget_component_id')->nullable()->constrained('budget_components')->nullOnDelete();
            $table->foreignId('budget_subcomponent_id')->nullable()->constrained('budget_subcomponents')->nullOnDelete();
            $table->foreignId('budget_account_id')->nullable()->constrained('budget_accounts')->nullOnDelete();
            $table->foreignId('budget_subaccount_id')->nullable()->constrained('budget_subaccounts')->nullOnDelete();

            // 5. Line Item Details & Financial Specifications (Safe Decimal for Rupiah)
            $table->text('description')->comment('Item specification / uraian belanja rincian RBA');
            $table->decimal('volume', 12, 2)->default(1.00);
            $table->string('unit', 50)->default('Kegiatan')->comment('Satuan ukur, e.g. OJ, OB, Bulan, Paket');
            $table->decimal('unit_price', 15, 2)->default(0.00)->comment('Harga satuan standar');
            $table->decimal('budget_amount', 15, 2)->default(0.00)->comment('Total pagu baris = volume x unit_price');

            // 6. Source, Import & Audit Metadata
            $table->foreignId('import_history_id')->nullable()->constrained('import_histories')->nullOnDelete();
            $table->unsignedInteger('source_row_index')->nullable()->comment('Original row index in imported file');
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE, REVISED, ARCHIVED');
            $table->json('source_metadata')->nullable()->comment('Additional raw JSON metadata from import or SAKTI');

            $table->timestamps();

            // 7. Contextual Unique Constraint & Indexes
            $table->unique(['budget_version_id', 'department_id', 'rba_sequence_no'], 'bline_version_dept_rba_unique');
            $table->index(['budget_version_id', 'department_id'], 'bline_version_dept_idx');
            $table->index(['budget_bucket_id'], 'bline_bucket_idx');
            $table->index(['budget_subcomponent_id', 'budget_account_id'], 'bline_subcomp_acc_idx');
        });

        // Add optional budget_line_id reference to submissions table
        if (! Schema::hasColumn('submissions', 'budget_line_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->foreignId('budget_line_id')->nullable()->after('budget_bucket_id')->constrained('budget_lines')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'budget_line_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropForeign(['budget_line_id']);
                $table->dropColumn(['budget_line_id']);
            });
        }

        Schema::dropIfExists('budget_lines');
    }
};
