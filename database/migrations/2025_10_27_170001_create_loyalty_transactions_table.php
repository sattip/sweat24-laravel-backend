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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points'); // Positive for earning, negative for spending
            $table->enum('type', ['earn', 'deduction', 'expiry', 'adjustment', 'refund']);
            $table->string('source')->nullable(); // 'payment', 'booking', 'manual', etc.
            $table->unsignedBigInteger('source_id')->nullable(); // ID of related record
            $table->text('description')->nullable();
            $table->integer('balance_after')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
