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
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade');
            $table->integer('points_used'); // Πόντοι που χρησιμοποιήθηκαν
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected', 'used', 'expired'
            $table->timestamp('redeemed_at');
            $table->timestamp('expires_at')->nullable(); // Πότε λήγει το δώρο
            $table->timestamp('used_at')->nullable(); // Πότε χρησιμοποιήθηκε
            $table->string('redemption_code')->unique(); // Μοναδικός κωδικός εξαργύρωσης
            $table->text('admin_notes')->nullable();
            $table->json('reward_snapshot'); // Snapshot του reward την ώρα της εξαργύρωσης
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['redemption_code']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
    }
};
