<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class NotificationFilter extends Model
{
    protected $fillable = [
        'name',
        'description',
        'criteria',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active filters.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Apply the filter criteria to a user query.
     */
    public function applyToQuery(Builder $query): Builder
    {
        $criteria = $this->criteria;

        // Filter by package type
        if (!empty($criteria['package_types'])) {
            $query->whereHas('userPackages', function ($q) use ($criteria) {
                $q->whereHas('package', function ($p) use ($criteria) {
                    $p->whereIn('type', $criteria['package_types']);
                });
            });
        }

        // Filter by membership status
        if (!empty($criteria['membership_status'])) {
            switch ($criteria['membership_status']) {
                case 'active':
                    $query->whereHas('userPackages', function ($q) {
                        $q->where('status', 'active')
                          ->where('expires_at', '>', now());
                    });
                    break;
                case 'expired':
                    $query->whereHas('userPackages', function ($q) {
                        $q->where('expires_at', '<=', now());
                    });
                    break;
                case 'trial':
                    $query->whereHas('userPackages', function ($q) {
                        $q->where('is_trial', true);
                    });
                    break;
            }
        }

        // Filter by class attendance
        if (!empty($criteria['class_attendance'])) {
            $days = $criteria['class_attendance']['days'] ?? 30;
            $minClasses = $criteria['class_attendance']['min_classes'] ?? 0;
            $maxClasses = $criteria['class_attendance']['max_classes'] ?? null;

            $query->whereHas('bookings', function ($q) use ($days, $minClasses, $maxClasses) {
                $q->where('created_at', '>=', now()->subDays($days))
                  ->where('status', 'confirmed')
                  ->groupBy('user_id')
                  ->havingRaw('COUNT(*) >= ?', [$minClasses]);
                
                if ($maxClasses !== null) {
                    $q->havingRaw('COUNT(*) <= ?', [$maxClasses]);
                }
            });
        }

        // Filter by registration date
        if (!empty($criteria['registration_date'])) {
            if (!empty($criteria['registration_date']['from'])) {
                $query->where('created_at', '>=', $criteria['registration_date']['from']);
            }
            if (!empty($criteria['registration_date']['to'])) {
                $query->where('created_at', '<=', $criteria['registration_date']['to']);
            }
        }

        // Filter by specific user IDs
        if (!empty($criteria['user_ids'])) {
            $query->whereIn('id', $criteria['user_ids']);
        }

        // Filter by tags (if implemented)
        if (!empty($criteria['tags'])) {
            $query->whereJsonContains('tags', $criteria['tags']);
        }

        return $query;
    }

    /**
     * Get users matching this filter.
     */
    public function getMatchingUsers()
    {
        return $this->applyToQuery(User::query())->get();
    }

    /**
     * Get count of users matching this filter.
     */
    public function getMatchingUsersCount(): int
    {
        return $this->applyToQuery(User::query())->count();
    }
}
