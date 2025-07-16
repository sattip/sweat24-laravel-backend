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
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add new columns for comprehensive activity tracking
            $table->string('activity_type')->after('user_id'); // registration, booking, cancellation, payment, etc.
            $table->string('model_type')->nullable()->after('activity_type'); // App\Models\Booking, App\Models\User, etc.
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            $table->json('properties')->nullable()->after('action'); // Additional data
            $table->string('ip_address', 45)->nullable()->after('properties');
            $table->text('user_agent')->nullable()->after('ip_address');
            
            // Remove date column and use timestamps instead
            $table->dropColumn('date');
            
            // Add indexes for performance
            $table->index('activity_type');
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['model_type', 'model_id']);
            
            $table->dropColumn(['activity_type', 'model_type', 'model_id', 'properties', 'ip_address', 'user_agent']);
            $table->date('date')->after('user_id');
        });
    }
};