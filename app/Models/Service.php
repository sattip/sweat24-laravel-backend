<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'trial_price',
        'is_active',
        'display_order',
        'allows_trial',
        'max_trial_per_user',
    ];

    protected $casts = [
        'trial_price' => 'decimal:2',
        'is_active' => 'boolean',
        'allows_trial' => 'boolean',
        'max_trial_per_user' => 'integer',
        'display_order' => 'integer',
    ];

    // Relationships
    public function packages()
    {
        return $this->belongsToMany(Package::class);
    }

    public function gymClasses()
    {
        return $this->hasMany(GymClass::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function trialAppointments()
    {
        return $this->hasMany(TrialAppointment::class);
    }

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    // Boot method για auto-generation του slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (!$service->slug) {
                $service->slug = Str::slug($service->name);
            }
        });

        static::updating(function ($service) {
            if ($service->isDirty('name') && !$service->isDirty('slug')) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * Ελέγχει αν ο χρήστης μπορεί να κάνει δοκιμαστικό ραντεβού για αυτή την υπηρεσία
     *
     * Λογική:
     * - Αν ο χρήστης έχει ΕΝΕΡΓΗ συνδρομή για αυτή την υπηρεσία → επιτρέπονται απεριόριστα δοκιμαστικά
     * - Αν ο χρήστης ΔΕΝ έχει ενεργή συνδρομή ή έχει ληγμένη → ισχύει ο περιορισμός max_trial_per_user
     *
     * Ενεργή συνδρομή θεωρείται:
     * - status = 'active'
     * - is_frozen = false
     * - expiry_date > now() (ή null)
     * - remaining_sessions > 0
     *
     * @param User $user
     * @return bool
     */
    public function allowsTrialForUser(User $user)
    {
        if (!$this->allows_trial) {
            return false;
        }

        // Έλεγχος αν ο χρήστης έχει ενεργή συνδρομή για αυτή την υπηρεσία
        $hasActiveSubscription = $user->userPackages()
            ->whereHas('package', function($query) {
                $query->where('service_id', $this->id);
            })
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->whereRaw('(expiry_date IS NULL OR expiry_date > ?)', [now()])
            ->where('remaining_sessions', '>', 0)
            ->exists();

        // Αν έχει ενεργή συνδρομή, επιτρέπονται απεριόριστα δοκιμαστικά
        if ($hasActiveSubscription) {
            return true;
        }

        // Αν δεν έχει ενεργή συνδρομή, ισχύει ο περιορισμός max_trial_per_user
        $trialCount = $this->trialAppointments()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->count();

        return $trialCount < $this->max_trial_per_user;
    }

    public function getTrialCountForUser(User $user)
    {
        return $this->trialAppointments()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->count();
    }
}
