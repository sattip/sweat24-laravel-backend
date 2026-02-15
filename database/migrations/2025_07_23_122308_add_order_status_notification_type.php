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
        Schema::table('notifications', function (Blueprint $table) {
            // Modify the type enum to include order_status notification type
            $table->enum('type', [
                'info', 
                'warning', 
                'success', 
                'error',
                'offer',           // Προσφορά
                'party_event',     // Πάρτι/Εκδήλωση
                'order_status'     // Κατάσταση Παραγγελίας
            ])->default('info')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Revert back to previous enum values
            $table->enum('type', [
                'info', 
                'warning', 
                'success', 
                'error',
                'offer',           // Προσφορά
                'party_event'      // Πάρτι/Εκδήλωση
            ])->default('info')->change();
        });
    }
};
