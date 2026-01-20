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
            // Payment tracking fields
            if (!Schema::hasColumn('user_packages', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('custom_assigned_at');
            }
            if (!Schema::hasColumn('user_packages', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }
            if (!Schema::hasColumn('user_packages', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('payment_status');
            }
            if (!Schema::hasColumn('user_packages', 'amount_remaining')) {
                $table->decimal('amount_remaining', 10, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('user_packages', 'installments')) {
                $table->integer('installments')->default(1)->after('amount_remaining');
            }
            if (!Schema::hasColumn('user_packages', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('installments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $columns = ['payment_method', 'payment_status', 'amount_paid', 'amount_remaining', 'installments', 'payment_notes'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('user_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
