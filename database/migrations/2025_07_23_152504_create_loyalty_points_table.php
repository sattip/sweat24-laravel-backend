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
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Μπορεί να είναι θετικό ή αρνητικό
            $table->string('type'); // 'earned', 'redeemed', 'expired', 'bonus'
            $table->string('source'); // 'payment', 'redemption', 'admin_adjustment', 'bonus'
            $table->string('description');
            $table->string('reference_type')->nullable(); // Payment, LoyaltyRedemption, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the related record
            $table->decimal('balance_after', 10, 2); // Balance after this transaction
            $table->timestamp('expires_at')->nullable(); // For points that expire
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};
