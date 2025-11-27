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
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('opened_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('opening_amount', 10, 2);
            $table->decimal('expected_closing_amount', 10, 2)->nullable();
            $table->decimal('actual_closing_amount', 10, 2)->nullable();
            $table->decimal('discrepancy', 10, 2)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
