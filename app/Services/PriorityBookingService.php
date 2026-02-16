<?php

namespace App\Services;

use App\Models\User;
use App\Models\GymClass;
use App\Models\Booking;
use App\Models\PriorityBookingSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PriorityBookingService
{
    protected $settings = null;

    protected function getSettings()
    {
        if (!$this->settings) {
            try {
                $this->settings = PriorityBookingSettings::getSettings();
            } catch (\Exception $e) {
                // Return default settings if table doesn't exist yet
                $this->settings = (object) [
                    'default_priority_seats' => 5,
                    'priority_advance_hours' => 48,
                    'priority_release_hours' => 24,
                    'auto_release_enabled' => true,
                    'priority_system_enabled' => true,
                    'priority_packages' => [],
                ];
            }
        }
        return $this->settings;
    }

    /**
     * Check if a user can book a class
     */
    public function canUserBookClass(User $user, GymClass $class): array
    {
        // Check if priority system is enabled
        if (!$this->getSettings()->priority_system_enabled) {
            return [
                'can_book' => $class->hasAvailableSpots(),
                'is_priority' => false,
                'reason' => $class->hasAvailableSpots() ? null : 'Class is full'
            ];
        }

        $classDateTime = Carbon::parse($class->date . ' ' . $class->time);
        $hoursUntilClass = now()->diffInHours($classDateTime, false);

        // Check if user has priority access
        $hasPriority = $user->hasActivePriorityBooking();
        
        // Check if class is in priority window
        $inPriorityWindow = $hoursUntilClass > $this->getSettings()->priority_release_hours;
        
        // Priority users can book if:
        // 1. They have priority AND
        // 2. Class is in priority window AND
        // 3. Priority seats are available
        if ($hasPriority && $inPriorityWindow && $class->hasPrioritySeatsAvailable()) {
            return [
                'can_book' => true,
                'is_priority' => true,
                'available_seats' => $class->availablePrioritySeats(),
                'reason' => null
            ];
        }

        // Check if priority seats have been released (24h before class)
        $priorityReleased = $hoursUntilClass <= $this->getSettings()->priority_release_hours;

        // Non-priority users can book if:
        // 1. Priority period has ended OR
        // 2. Regular seats are available
        if (!$hasPriority) {
            if ($inPriorityWindow && !$priorityReleased) {
                // Still in priority window
                if ($class->hasRegularSeatsAvailable()) {
                    return [
                        'can_book' => true,
                        'is_priority' => false,
                        'available_seats' => $class->availableRegularSeats(),
                        'reason' => null
                    ];
                } else {
                    return [
                        'can_book' => false,
                        'is_priority' => false,
                        'reason' => 'Only priority seats available. Priority booking period ends ' . 
                                   $this->getSettings()->priority_release_hours . ' hours before class.'
                    ];
                }
            }
        }

        // After priority release, anyone can book any available seat
        if ($priorityReleased) {
            $class->releasePrioritySeats();
            return [
                'can_book' => $class->hasAvailableSpots(),
                'is_priority' => false,
                'available_seats' => $class->availableSpots(),
                'reason' => $class->hasAvailableSpots() ? null : 'Class is full'
            ];
        }

        // Default case - check regular availability
        return [
            'can_book' => $class->hasAvailableSpots(),
            'is_priority' => false,
            'available_seats' => $class->availableSpots(),
            'reason' => $class->hasAvailableSpots() ? null : 'Class is full'
        ];
    }

    /**
     * Create a booking for a class
     */
    public function createBooking(User $user, GymClass $class, array $bookingData = []): ?Booking
    {
        $canBook = $this->canUserBookClass($user, $class);

        if (!$canBook['can_book']) {
            throw new \Exception($canBook['reason'] ?? 'Cannot book this class');
        }

        return DB::transaction(function () use ($user, $class, $canBook, $bookingData) {
            // Determine if this is a priority booking
            $isPriorityBooking = $canBook['is_priority'];

            // Book the appropriate seat type
            if ($isPriorityBooking) {
                if (!$class->bookPrioritySeat()) {
                    throw new \Exception('Failed to book priority seat');
                }
            } else {
                if (!$class->bookRegularSeat()) {
                    throw new \Exception('Failed to book regular seat');
                }
            }

            // Create the booking record
            $booking = Booking::create(array_merge($bookingData, [
                'user_id' => $user->id,
                'class_id' => $class->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'class_name' => $class->name,
                'instructor' => $class->instructor,
                'date' => $class->date,
                'time' => $class->time,
                'type' => $class->type,
                'booking_type' => 'class',
                'location' => $class->location,
                'status' => 'confirmed',
                'is_priority_booking' => $isPriorityBooking,
                'booking_time' => now(),
            ]));

            return $booking;
        });
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $class = GymClass::find($booking->class_id);

            if (!$class) {
                throw new \Exception('Class not found');
            }

            // Update class participant counts
            if ($booking->is_priority_booking) {
                $class->cancelPriorityBooking();
            } else {
                $class->decrement('current_participants');
            }

            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'User cancelled',
            ]);

            return true;
        });
    }

    /**
     * Grant priority booking to a user
     */
    public function grantPriorityAccess(User $user, ?Carbon $expiresAt = null, ?int $hoursAdvance = null): void
    {
        $hoursAdvance = $hoursAdvance ?? $this->getSettings()->priority_advance_hours;
        
        $user->grantPriorityBooking($expiresAt, $hoursAdvance);
    }

    /**
     * Revoke priority booking from a user
     */
    public function revokePriorityAccess(User $user): void
    {
        $user->revokePriorityBooking();
    }

    /**
     * Check which users should have priority based on packages
     */
    public function updatePriorityUsersFromPackages(): void
    {
        if (!$this->getSettings()->priority_packages) {
            return;
        }

        // Grant priority to users with priority packages
        $usersWithPriorityPackages = User::whereHas('userPackages', function ($query) {
            $query->whereIn('package_id', $this->getSettings()->priority_packages)
                  ->where('status', 'active')
                  ->where(function ($q) {
                      $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                  });
        })->get();

        foreach ($usersWithPriorityPackages as $user) {
            if (!$user->hasActivePriorityBooking()) {
                // Get package expiration to sync with priority expiration
                $packageExpiration = $user->userPackages()
                    ->whereIn('package_id', $this->getSettings()->priority_packages)
                    ->where('status', 'active')
                    ->min('expires_at');

                $this->grantPriorityAccess($user, $packageExpiration);
            }
        }

        // Revoke priority from users who no longer have priority packages
        $usersToRevoke = User::where('has_priority_booking', true)
            ->whereDoesntHave('userPackages', function ($query) {
                $query->whereIn('package_id', $this->getSettings()->priority_packages)
                      ->where('status', 'active')
                      ->where(function ($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      });
            })->get();

        foreach ($usersToRevoke as $user) {
            $this->revokePriorityAccess($user);
        }
    }

    /**
     * Get class availability information for a user
     */
    public function getClassAvailabilityForUser(GymClass $class, User $user): array
    {
        $canBook = $this->canUserBookClass($user, $class);
        $classDateTime = Carbon::parse($class->date . ' ' . $class->time);
        $hoursUntilClass = now()->diffInHours($classDateTime, false);
        
        return array_merge($class->getAvailabilityInfo(), [
            'user_can_book' => $canBook['can_book'],
            'user_has_priority' => $user->hasActivePriorityBooking(),
            'booking_as_priority' => $canBook['is_priority'],
            'booking_reason' => $canBook['reason'],
            'hours_until_class' => $hoursUntilClass,
            'priority_window_active' => $hoursUntilClass > $this->getSettings()->priority_release_hours,
            'priority_release_time' => $classDateTime->subHours($this->getSettings()->priority_release_hours)->toDateTimeString(),
        ]);
    }

    /**
     * Release priority seats for classes starting within release window
     */
    public function releaseUpcomingPrioritySeats(): int
    {
        $releaseTime = now()->addHours($this->getSettings()->priority_release_hours);
        
        $classes = GymClass::where('priority_booking_enabled', true)
            ->where('priority_seats', '>', 0)
            ->whereRaw("CONCAT(date, ' ', time) <= ?", [$releaseTime])
            ->whereRaw("CONCAT(date, ' ', time) >= ?", [now()])
            ->get();

        $releasedCount = 0;
        foreach ($classes as $class) {
            if ($class->shouldReleasePrioritySeats()) {
                $class->releasePrioritySeats();
                $releasedCount++;
            }
        }

        return $releasedCount;
    }
}