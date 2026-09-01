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
        Schema::create('performance_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('objective_code')->nullable()->index();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('target_2026')->nullable();
            $table->string('framework')->default('SAKIP_RPKA');
            $table->string('data_status')->default('OFFICIAL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_indicators');
    }
};
