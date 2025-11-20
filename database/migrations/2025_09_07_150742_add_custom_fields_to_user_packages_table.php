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
            $table->boolean('is_custom_package')->default(false)->after('auto_renew');
            $table->decimal('custom_price', 10, 2)->nullable()->after('is_custom_package');
            $table->integer('custom_sessions')->nullable()->after('custom_price');
            $table->integer('custom_duration_days')->nullable()->after('custom_sessions');
            $table->text('custom_notes')->nullable()->after('custom_duration_days');
            $table->string('assigned_by')->nullable()->after('custom_notes'); // Admin who assigned the custom package
            $table->timestamp('custom_assigned_at')->nullable()->after('assigned_by');
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
