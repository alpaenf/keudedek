<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update budget_buckets table
        Schema::table('budget_buckets', function (Blueprint $table) {
            if (! Schema::hasColumn('budget_buckets', 'budget_bucket_name')) {
                $table->string('budget_bucket_name')->nullable()->after('account_name');
            }
            if (! Schema::hasColumn('budget_buckets', 'description')) {
                $table->text('description')->nullable()->after('budget_bucket_name');
            }
        });

        // 2. Create transaction_types table
        if (! Schema::hasTable('transaction_types')) {
            Schema::create('transaction_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique(); // LS, UP, TUP, OTHER
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Create document_types table
        if (! Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->boolean('is_required')->default(false);
                $table->json('applicable_transaction_types')->nullable();
                $table->integer('max_file_size_mb')->default(10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Create workflow_definitions table
        if (! Schema::hasTable('workflow_definitions')) {
            Schema::create('workflow_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->foreignId('transaction_type_id')->nullable()->constrained('transaction_types')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Create workflow_steps table
        if (! Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
                $table->integer('sequence')->default(1);
                $table->string('role', 30); // PTK, KAJUR, PTU, KABAG, WAKIL_DEKAN, DEKAN
                $table->string('name', 100);
                $table->boolean('can_approve')->default(true);
                $table->boolean('can_return')->default(true);
                $table->boolean('can_reject')->default(true);
                $table->boolean('requires_signoff')->default(false);
                $table->boolean('reserve_trigger')->default(false);
                $table->boolean('final_trigger')->default(false);
                $table->timestamps();
            });
        }

        // 6. Create submission_templates table
        if (! Schema::hasTable('submission_templates')) {
            Schema::create('submission_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->foreignId('transaction_type_id')->nullable()->constrained('transaction_types')->nullOnDelete();
                $table->string('version', 20)->default('v1.0');
                $table->date('effective_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 7. Create submission_template_fields table
        if (! Schema::hasTable('submission_template_fields')) {
            Schema::create('submission_template_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_template_id')->constrained('submission_templates')->cascadeOnDelete();
                $table->string('field_code', 50);
                $table->string('label', 150);
                $table->string('data_type', 30)->default('TEXT'); // TEXT, NUMBER, DATE, SELECT, FILE, TEXTAREA
                $table->boolean('is_required')->default(false);
                $table->boolean('is_editable')->default(true);
                $table->string('import_column', 50)->nullable();
                $table->string('validation_rules')->nullable();
                $table->string('default_value')->nullable();
                $table->integer('order_index')->default(0);
                $table->timestamps();
            });
        }

        // 8. Update submissions table
        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('submission_number');
            }
            if (! Schema::hasColumn('submissions', 'transaction_type_id')) {
                $table->foreignId('transaction_type_id')->nullable()->after('fiscal_year_id')->constrained('transaction_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('submissions', 'submission_template_id')) {
                $table->foreignId('submission_template_id')->nullable()->after('transaction_type_id')->constrained('submission_templates')->nullOnDelete();
            }
            if (! Schema::hasColumn('submissions', 'beneficiary_name')) {
                $table->string('beneficiary_name')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('submissions', 'current_workflow_step_id')) {
                $table->foreignId('current_workflow_step_id')->nullable()->after('status')->constrained('workflow_steps')->nullOnDelete();
            }
            if (! Schema::hasColumn('submissions', 'electronic_signoff_hash')) {
                $table->string('electronic_signoff_hash')->nullable()->after('notes');
            }
        });

        // 9. Create submission_documents table
        if (! Schema::hasTable('submission_documents')) {
            Schema::create('submission_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
                $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('mime_type', 100);
                $table->string('extension', 20);
                $table->unsignedBigInteger('file_size');
                $table->string('checksum_sha256', 64)->nullable();
                $table->foreignId('uploaded_by')->constrained('users');
                $table->timestamps();
            });
        }

        // 10. Create approvals table
        if (! Schema::hasTable('approvals')) {
            Schema::create('approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->string('role', 30);
                $table->string('decision', 30); // APPROVED, RETURNED, REJECTED
                $table->text('comment')->nullable();
                $table->string('document_hash', 64)->nullable();
                $table->json('signature_payload')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }

        // 11. Create submission_status_histories table
        if (! Schema::hasTable('submission_status_histories')) {
            Schema::create('submission_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role', 30)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 12. Create submission_import_batches table
        if (! Schema::hasTable('submission_import_batches')) {
            Schema::create('submission_import_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_number', 50)->unique();
                $table->foreignId('user_id')->constrained('users');
                $table->integer('total_rows')->default(0);
                $table->integer('valid_rows')->default(0);
                $table->integer('invalid_rows')->default(0);
                $table->string('status', 30)->default('PENDING'); // PENDING, COMMITTED, CANCELLED
                $table->timestamps();
            });
        }

        // 13. Create submission_import_stagings table
        if (! Schema::hasTable('submission_import_stagings')) {
            Schema::create('submission_import_stagings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('submission_import_batches')->cascadeOnDelete();
                $table->integer('row_number');
                $table->string('reference_no')->nullable();
                $table->string('fiscal_year', 10)->nullable();
                $table->string('department_code', 20)->nullable();
                $table->string('transaction_type_code', 20)->nullable();
                $table->string('title')->nullable();
                $table->string('account_code', 30)->nullable();
                $table->decimal('amount', 18, 2)->nullable();
                $table->string('beneficiary')->nullable();
                $table->text('notes')->nullable();
                $table->string('validation_status', 20)->default('VALID'); // VALID, INVALID
                $table->json('error_messages')->nullable();
                $table->json('parsed_items')->nullable();
                $table->timestamps();
            });
        }

        // 14. Create rule_configs table
        if (! Schema::hasTable('rule_configs')) {
            Schema::create('rule_configs', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 30)->unique();
                $table->string('rule_name', 150);
                $table->string('category', 20)->default('EWS'); // EWS, RBC
                $table->json('parameters')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 15. Update early_warnings table
        Schema::table('early_warnings', function (Blueprint $table) {
            if (! Schema::hasColumn('early_warnings', 'lifecycle_state')) {
                $table->string('lifecycle_state', 30)->default('OPEN')->after('status'); // OPEN, ACKNOWLEDGED, RESOLVED
            }
            if (! Schema::hasColumn('early_warnings', 'rule_config_id')) {
                $table->foreignId('rule_config_id')->nullable()->after('lifecycle_state')->constrained('rule_configs')->nullOnDelete();
            }
        });

        // 16. Create notifications table
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role', 30)->nullable();
                $table->string('title', 150);
                $table->text('message');
                $table->string('type', 30)->default('SYSTEM'); // SUBMISSION, APPROVAL, WARNING, REVISION, SYSTEM
                $table->string('link_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('submission_import_stagings');
        Schema::dropIfExists('submission_import_batches');
        Schema::dropIfExists('submission_status_histories');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('submission_documents');
        Schema::dropIfExists('submission_template_fields');
        Schema::dropIfExists('submission_templates');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('rule_configs');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('transaction_types');
    }
};
