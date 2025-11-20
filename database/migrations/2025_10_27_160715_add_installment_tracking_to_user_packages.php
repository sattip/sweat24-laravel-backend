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
            $table->integer('installments_paid')->default(0)->after('installments');
            $table->string('installment_frequency', 20)->nullable()->after('installments_paid');
            $table->text('extension_notes')->nullable()->after('payment_notes');
            $table->date('extension_from')->nullable()->after('extension_notes');
            $table->date('extension_to')->nullable()->after('extension_from');
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
