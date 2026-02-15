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
            $table->integer('installments_paid')->default(0);
            $table->string('installment_frequency', 20)->nullable();
            $table->text('extension_notes')->nullable();
            $table->date('extension_from')->nullable();
            $table->date('extension_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn(['installments_paid', 'installment_frequency', 'extension_notes', 'extension_from', 'extension_to']);
        });
    }
};
