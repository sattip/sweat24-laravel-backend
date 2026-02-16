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
        Schema::create('bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g., 'package_extension', 'pricing_adjustment'
            $table->foreignId('performed_by')->constrained('users');
            $table->integer('target_count')->default(0);
            $table->integer('successful_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'completed_with_errors', 'cancelled', 'failed'])->default('pending');
            $table->json('filters')->nullable(); // Store filter criteria
            $table->json('operation_data')->nullable(); // Store operation parameters
            $table->json('errors')->nullable(); // Store error details
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['performed_by', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_operations');
    }
};
