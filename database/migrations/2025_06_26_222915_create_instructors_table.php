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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('specialties');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('monthly_bonus', 8, 2)->nullable();
            $table->decimal('commission_rate', 5, 4)->nullable();
            $table->enum('contract_type', ['hourly', 'salary', 'commission']);
            $table->enum('status', ['active', 'inactive', 'vacation'])->default('active');
            $table->date('join_date');
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
