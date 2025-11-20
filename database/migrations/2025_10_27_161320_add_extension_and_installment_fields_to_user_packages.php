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
            // Installment tracking fields
            if (!Schema::hasColumn('user_packages', 'installments_paid')) {
                $table->integer('installments_paid')->default(0)->after('installments');
            }
            if (!Schema::hasColumn('user_packages', 'installment_frequency')) {
                $table->string('installment_frequency', 20)->nullable()->after('installments_paid');
            }

            // Extension tracking fields
            if (!Schema::hasColumn('user_packages', 'extension_notes')) {
                $table->text('extension_notes')->nullable()->after('payment_notes');
            }
            if (!Schema::hasColumn('user_packages', 'extension_from')) {
                $table->date('extension_from')->nullable()->after('extension_notes');
            }
            if (!Schema::hasColumn('user_packages', 'extension_to')) {
                $table->date('extension_to')->nullable()->after('extension_from');
            }

            // Pause tracking field
            if (!Schema::hasColumn('user_packages', 'pause_reason')) {
                $table->text('pause_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $columns = ['installments_paid', 'installment_frequency', 'extension_notes', 'extension_from', 'extension_to', 'pause_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('user_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
