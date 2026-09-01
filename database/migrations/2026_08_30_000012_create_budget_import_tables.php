<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->string('status', 20)->default('PENDING'); // PENDING, COMMITTED, CANCELLED
            $table->timestamps();
        });

        Schema::create('budget_import_stagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_history_id')->constrained('import_histories')->cascadeOnDelete();
            $table->string('department_code', 30);
            $table->integer('fiscal_year');
            $table->string('funding_source_code', 30);
            $table->string('account_code', 30);
            $table->string('account_name', 255);
            $table->decimal('initial_budget', 15, 2);
            $table->string('status', 20)->default('VALID'); // VALID, INVALID
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_import_stagings');
        Schema::dropIfExists('import_histories');
    }
};
