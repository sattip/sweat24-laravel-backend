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
        Schema::table('users', function (Blueprint $table) {
            // Add registration_status enum after the status column
            $table->enum('registration_status', [
                'pending_terms', 
                'pending_signature', 
                'completed'
            ])->default('pending_terms');
            
            // Add terms acceptance timestamp
            $table->timestamp('terms_accepted_at')->nullable();
            
            // Add registration completion timestamp
            $table->timestamp('registration_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_status',
                'terms_accepted_at', 
                'registration_completed_at'
            ]);
        });
    }
};
