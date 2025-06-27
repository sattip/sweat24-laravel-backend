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
        Schema::create('cash_register_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'withdrawal']);
            $table->decimal('amount', 10, 2);
            $table->text('description');
            $table->string('category');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // admin who made the entry
            $table->enum('payment_method', ['cash', 'card', 'transfer'])->nullable();
            $table->string('related_entity_id')->nullable();
            $table->enum('related_entity_type', ['customer', 'package', 'expense', 'other'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_entries');
    }
};
