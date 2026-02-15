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
        Schema::table('owner_notifications', function (Blueprint $table) {
            // Add user_id to track which admin should see the notification
            $table->unsignedBigInteger('user_id')->nullable();

            // Add related model fields for polymorphic relationships
            $table->string('related_model_type')->nullable();
            $table->unsignedBigInteger('related_model_id')->nullable();

            // Add metadata field for storing additional structured data
            $table->json('metadata')->nullable();

            // Update type enum to include new types
            $table->dropColumn('type');
        });

        // Re-add type with updated enum values
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->enum('type', ['graceful_cancellation', 'package_extension', 'special_price', 'general']);
        });

        // Make trainer_name and customer_name nullable (not always needed)
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->string('trainer_name')->nullable()->change();
            $table->string('customer_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'related_model_type', 'related_model_id', 'metadata']);
            $table->string('trainer_name')->nullable(false)->change();
            $table->string('customer_name')->nullable(false)->change();
        });

        // Restore original type enum
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->enum('type', ['graceful_cancellation', 'package_extension', 'general']);
        });
    }
};
