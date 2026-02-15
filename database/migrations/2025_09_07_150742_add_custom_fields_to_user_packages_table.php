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
        Schema::table('user_packages', function (Blueprint $table) {
            // Custom package fields for personalized pricing and terms
            $table->boolean('is_custom_package')->default(false);
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->integer('custom_sessions')->nullable();
            $table->integer('custom_duration_days')->nullable();
            $table->text('custom_notes')->nullable();
            $table->string('assigned_by')->nullable(); // Admin who assigned the custom package
            $table->timestamp('custom_assigned_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn([
                'is_custom_package',
                'custom_price',
                'custom_sessions',
                'custom_duration_days',
                'custom_notes',
                'assigned_by',
                'custom_assigned_at'
            ]);
        });
    }
};
