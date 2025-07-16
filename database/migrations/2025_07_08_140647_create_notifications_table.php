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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'error'])->default('info');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->json('channels')->nullable(); // ['in_app', 'email', 'sms']
            $table->json('filters')->nullable(); // Store filter criteria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sent', 'failed'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
