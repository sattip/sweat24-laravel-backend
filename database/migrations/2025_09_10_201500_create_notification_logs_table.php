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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->text('push_token');
            $table->string('platform', 20);
            $table->enum('status', ['success', 'failed', 'invalid_token']);
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['notification_id', 'status'], 'idx_notification_status');
            $table->index(['user_id', 'sent_at'], 'idx_user_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};