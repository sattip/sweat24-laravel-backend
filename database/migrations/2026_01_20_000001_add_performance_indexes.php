<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes are added to optimize common query patterns identified
     * in the application's search and filter operations.
     */
    public function up(): void
    {
        // Bookings table - commonly filtered by date, status, and instructor
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('date', 'bookings_date_index');
            $table->index('status', 'bookings_status_index');
            $table->index(['date', 'status'], 'bookings_date_status_index');
            $table->index(['user_id', 'date'], 'bookings_user_date_index');
        });

        // User packages table - commonly filtered by status and expiry_date
        Schema::table('user_packages', function (Blueprint $table) {
            $table->index('status', 'user_packages_status_index');
            $table->index('expiry_date', 'user_packages_expiry_date_index');
            $table->index(['status', 'expiry_date'], 'user_packages_status_expiry_index');
        });

        // Activity logs - commonly filtered by activity_type and created_at
        // Note: activity_type and created_at indexes already exist from 2025_07_08_180000_update_activity_logs_table_for_dashboard
        Schema::table('activity_logs', function (Blueprint $table) {
            // Only add composite index that doesn't exist
            $table->index(['user_id', 'activity_type'], 'activity_logs_user_type_index');
        });

        // Users table - commonly searched and filtered
        Schema::table('users', function (Blueprint $table) {
            $table->index('status', 'users_status_index');
            $table->index('role', 'users_role_index');
            $table->index('created_at', 'users_created_at_index');
        });

        // Cash register entries - commonly filtered by type and date
        Schema::table('cash_register_entries', function (Blueprint $table) {
            $table->index('type', 'cash_register_entries_type_index');
            $table->index('created_at', 'cash_register_entries_created_at_index');
            $table->index(['type', 'created_at'], 'cash_register_entries_type_date_index');
        });

        // Notification recipients - commonly filtered by delivery_status and user_id
        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->index('delivery_status', 'notification_recipients_status_index');
            $table->index(['user_id', 'read_at'], 'notification_recipients_user_read_index');
        });

        // Payment installments - commonly filtered by status and due_date
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->index('status', 'payment_installments_status_index');
            $table->index('due_date', 'payment_installments_due_date_index');
        });

        // Gym classes - commonly filtered by date and status
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->index('date', 'gym_classes_date_index');
            $table->index('status', 'gym_classes_status_index');
            $table->index(['date', 'status'], 'gym_classes_date_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_date_index');
            $table->dropIndex('bookings_status_index');
            $table->dropIndex('bookings_date_status_index');
            $table->dropIndex('bookings_user_date_index');
        });

        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropIndex('user_packages_status_index');
            $table->dropIndex('user_packages_expiry_date_index');
            $table->dropIndex('user_packages_status_expiry_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            // Only drop the composite index we added
            $table->dropIndex('activity_logs_user_type_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_index');
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_created_at_index');
        });

        Schema::table('cash_register_entries', function (Blueprint $table) {
            $table->dropIndex('cash_register_entries_type_index');
            $table->dropIndex('cash_register_entries_created_at_index');
            $table->dropIndex('cash_register_entries_type_date_index');
        });

        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->dropIndex('notification_recipients_status_index');
            $table->dropIndex('notification_recipients_user_read_index');
        });

        Schema::table('payment_installments', function (Blueprint $table) {
            $table->dropIndex('payment_installments_status_index');
            $table->dropIndex('payment_installments_due_date_index');
        });

        Schema::table('gym_classes', function (Blueprint $table) {
            $table->dropIndex('gym_classes_date_index');
            $table->dropIndex('gym_classes_status_index');
            $table->dropIndex('gym_classes_date_status_index');
        });
    }
};
