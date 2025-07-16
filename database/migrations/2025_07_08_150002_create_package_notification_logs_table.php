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
        Schema::create('package_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // expiring_7_days, expiring_3_days, expired, renewed
            $table->string('channel'); // email, sms, in_app
            $table->boolean('sent_successfully')->default(false);
            $table->text('error_message')->nullable();
            $table->integer('days_until_expiry')->nullable();
            $table->timestamps();
            
            $table->index(['user_package_id', 'notification_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_notification_logs');
    }
};