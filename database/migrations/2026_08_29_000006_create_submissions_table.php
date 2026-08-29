<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_number', 50)->unique();
            $table->string('title');
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->foreignId('budget_bucket_id')->constrained('budget_buckets')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('DRAFT'); // DRAFT, SUBMITTED, REVIEW, RETURNED, APPROVED, RESERVED, PROCESSING, COMPLETED, REJECTED
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
