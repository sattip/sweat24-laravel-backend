<?php

namespace App\Auth;

/**
 * Token Abilities for Sanctum
 *
 * Defines the available token abilities (scopes) for API access control.
 * Use these when creating tokens to limit what actions the token can perform.
 */
class TokenAbilities
{
    // Basic abilities
    public const READ_PROFILE = 'read:profile';
    public const UPDATE_PROFILE = 'update:profile';

    // Booking abilities
    public const CREATE_BOOKING = 'create:booking';
    public const READ_BOOKINGS = 'read:bookings';
    public const CANCEL_BOOKING = 'cancel:booking';
    public const MANAGE_BOOKINGS = 'manage:bookings';

    // Package abilities
    public const READ_PACKAGES = 'read:packages';
    public const MANAGE_PACKAGES = 'manage:packages';

    // Class abilities
    public const READ_CLASSES = 'read:classes';
    public const MANAGE_CLASSES = 'manage:classes';

    // User management abilities
    public const READ_USERS = 'read:users';
    public const MANAGE_USERS = 'manage:users';
    public const APPROVE_USERS = 'approve:users';

    // Financial abilities
    public const READ_FINANCIALS = 'read:financials';
    public const MANAGE_FINANCIALS = 'manage:financials';
    public const MANAGE_CASH_REGISTER = 'manage:cash-register';

    // Admin abilities
    public const ADMIN_ACCESS = 'admin:access';
    public const VIEW_REPORTS = 'view:reports';
    public const MANAGE_SETTINGS = 'manage:settings';

    // Notification abilities
    public const READ_NOTIFICATIONS = 'read:notifications';
    public const MANAGE_NOTIFICATIONS = 'manage:notifications';

    /**
     * Get abilities for a member role.
     */
    public static function forMember(): array
    {
        return [
            self::READ_PROFILE,
            self::UPDATE_PROFILE,
            self::CREATE_BOOKING,
            self::READ_BOOKINGS,
            self::CANCEL_BOOKING,
            self::READ_PACKAGES,
            self::READ_CLASSES,
            self::READ_NOTIFICATIONS,
        ];
    }

    /**
     * Get abilities for a trainer role.
     */
    public static function forTrainer(): array
    {
        return array_merge(self::forMember(), [
            self::MANAGE_BOOKINGS,
            self::MANAGE_CLASSES,
            self::READ_USERS,
            self::READ_FINANCIALS,
            self::MANAGE_CASH_REGISTER,
        ]);
    }

    /**
     * Get abilities for an admin role.
     */
    public static function forAdmin(): array
    {
        return [
            // All abilities
            self::READ_PROFILE,
            self::UPDATE_PROFILE,
            self::CREATE_BOOKING,
            self::READ_BOOKINGS,
            self::CANCEL_BOOKING,
            self::MANAGE_BOOKINGS,
            self::READ_PACKAGES,
            self::MANAGE_PACKAGES,
            self::READ_CLASSES,
            self::MANAGE_CLASSES,
            self::READ_USERS,
            self::MANAGE_USERS,
            self::APPROVE_USERS,
            self::READ_FINANCIALS,
            self::MANAGE_FINANCIALS,
            self::MANAGE_CASH_REGISTER,
            self::ADMIN_ACCESS,
            self::VIEW_REPORTS,
            self::MANAGE_SETTINGS,
            self::READ_NOTIFICATIONS,
            self::MANAGE_NOTIFICATIONS,
        ];
    }

    /**
     * Get abilities based on user role.
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => self::forAdmin(),
            'trainer' => self::forTrainer(),
            default => self::forMember(),
        };
    }

    /**
     * Get all available abilities.
     */
    public static function all(): array
    {
        return self::forAdmin();
    }
}
