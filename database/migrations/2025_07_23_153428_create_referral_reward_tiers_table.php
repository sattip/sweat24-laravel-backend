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
        Schema::create('referral_reward_tiers', function (Blueprint $table) {
            $table->id();
            $table->integer('referrals_required'); // Αριθμός συστάσεων που απαιτούνται
            $table->string('name'); // Όνομα του tier (π.χ. "1η Σύσταση", "5η Σύσταση")
            $table->text('description'); // Περιγραφή του δώρου
            $table->string('reward_type'); // 'discount', 'free_month', 'personal_training', 'custom'
            $table->decimal('discount_percentage', 5, 2)->nullable(); // Ποσοστό έκπτωσης
            $table->decimal('discount_amount', 10, 2)->nullable(); // Σταθερή έκπτωση
            $table->integer('validity_days')->default(90); // Διάρκεια ισχύος σε ημέρες
            $table->boolean('quarterly_only')->default(true); // Μόνο για τρίμηνα πακέτα
            $table->boolean('next_renewal_only')->default(true); // Μόνο για την επόμενη ανανέωση
            $table->boolean('is_active')->default(true);
            $table->json('terms_conditions')->nullable(); // Όροι και προϋποθέσεις
            $table->timestamps();

            $table->unique('referrals_required'); // Κάθε tier είναι μοναδικό
            $table->index(['is_active', 'referrals_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_reward_tiers');
    }
};
