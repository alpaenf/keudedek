<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_warnings', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code', 50);
            $table->string('severity', 20); // CRITICAL, HIGH, MEDIUM, LOW, INFO
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('budget_bucket_id')->nullable()->constrained('budget_buckets')->nullOnDelete();
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('threshold_value', 15, 2)->default(0);
            $table->text('message');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, ACKNOWLEDGED, RESOLVED
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_warnings');
    }
};
