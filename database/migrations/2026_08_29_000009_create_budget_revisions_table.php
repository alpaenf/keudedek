<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revision_number', 50)->unique();
            $table->foreignId('budget_bucket_id')->constrained('budget_buckets')->cascadeOnDelete();
            $table->decimal('previous_amount', 15, 2);
            $table->decimal('revised_amount', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->text('reason');
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_revisions');
    }
};
