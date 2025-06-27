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
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('customer_name');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->string('package_name');
            $table->integer('installment_number');
            $table->integer('total_installments');
            $table->decimal('amount', 8, 2);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'transfer'])->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
