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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_type')->default('regular')->after('type');
            // 'regular' = Κανονική
            // 'trial' = Δοκιμαστική  
            // 'loyalty_gift' = Δώρο Ανταμοιβής
            // 'referral_gift' = Δώρο Συστάσεων
            // 'free' = Δωρεάν
            // 'promotional' = Προσφορά
            
            $table->index(['booking_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_type']);
            $table->dropColumn('booking_type');
        });
    }
};
