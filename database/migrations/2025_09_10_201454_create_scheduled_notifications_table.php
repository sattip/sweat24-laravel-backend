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
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['package_expiry_week', 'package_expiry_2days', 'appointment_reminder']);
            $table->string('title');
            $table->text('body');
            $table->datetime('scheduled_for');
            $table->unsignedBigInteger('related_id')->nullable()->comment('package_id ή appointment_id');
            $table->json('data')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['scheduled_for', 'is_sent'], 'idx_scheduled_pending');
            $table->index('user_id', 'idx_user_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};