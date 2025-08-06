<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'membership_type',
        'role',
        'join_date',
        'remaining_sessions',
        'total_sessions',
        'status',
        'registration_status',
        'terms_accepted_at',
        'registration_completed_at',
        'approved_at',
        'approved_by',
        'last_visit',
        'medical_history',
        'emergency_contact',
        'emergency_phone',
        'notes',
        'notification_preferences',
        'privacy_settings',
        'avatar',
        'password',
        'found_us_via',
        'referrer_id',
        'social_platform',
        'referral_code_or_name',
        'referral_validated',
        'referral_validated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'date',
            'date_of_birth' => 'date',
            'last_visit' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'registration_completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'notification_preferences' => 'array',
            'privacy_settings' => 'array',
            'referral_validated' => 'boolean',
            'referral_validated_at' => 'datetime',
        ];
    }

    public function packages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function signatures()
    {
        return $this->hasMany(Signature::class);
    }
    
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }
    
    public function notifications()
    {
        return $this->hasMany(NotificationRecipient::class);
    }
    
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }
    
    public function createdNotifications()
    {
        return $this->hasMany(Notification::class, 'created_by');
    }
    
    public function chatConversation()
    {
        return $this->hasOne(ChatConversation::class);
    }
    
    // Role check methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }
    
    public function isMember(): bool
    {
        return $this->role === 'member';
    }
    
    public function canAccessFinancials(): bool
    {
        return $this->isAdmin();
    }
    
    public function canAccessLimitedFinancials(): bool
    {
        return $this->isAdmin() || $this->isTrainer();
    }
    
    // Registration status check methods
    public function isPendingApproval(): bool
    {
        return $this->registration_status === 'pending_approval';
    }
    
    public function isPendingTerms(): bool
    {
        return $this->registration_status === 'pending_terms';
    }
    
    public function isPendingSignature(): bool
    {
        return $this->registration_status === 'pending_signature';
    }
    
    public function isRegistrationCompleted(): bool
    {
        return $this->registration_status === 'completed';
    }
    
    public function canBeApproved(): bool
    {
        return $this->isPendingApproval();
    }
    
    public function canAcceptTerms(): bool
    {
        return $this->isPendingTerms();
    }
    
    public function canCompleteRegistration(): bool
    {
        return $this->isPendingSignature();
    }
    
    public function getLatestSignature()
    {
        return $this->signatures()->latest()->first();
    }
    
    // Approval relationship
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function approvedUsers()
    {
        return $this->hasMany(User::class, 'approved_by');
    }
    
    // ============ LOYALTY SYSTEM RELATIONSHIPS ============
    
    /**
     * Λίστα πόντων του χρήστη
     */
    public function loyaltyPoints()
    {
        return $this->hasMany(LoyaltyPoint::class);
    }
    
    /**
     * Εξαργυρώσεις loyalty rewards
     */
    public function loyaltyRedemptions()
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * Referrals made by this user (as referrer)
     */
    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Referrals where this user was referred
     */
    public function referralsReceived()
    {
        return $this->hasMany(Referral::class, 'referred_user_id');
    }
    
    /**
     * Users referred by this user (direct relationship)
     */
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }
    
    /**
     * The user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
    
    /**
     * Υπολογισμός τρέχοντος υπολοίπου πόντων
     */
    public function getLoyaltyPointsBalanceAttribute()
    {
        return $this->loyaltyPoints()
                   ->where(function($query) {
                       $query->whereNull('expires_at')
                             ->orWhere('expires_at', '>', now());
                   })
                   ->sum('amount');
    }
    
    /**
     * Πόντοι που λήγουν σύντομα
     */
    public function getExpiringPointsAttribute()
    {
        return $this->loyaltyPoints()
                   ->where('type', 'earned')
                   ->where('expires_at', '<=', now()->addDays(30))
                   ->where('expires_at', '>', now())
                   ->sum('amount');
    }
    
    /**
     * Προσθήκη πόντων στον χρήστη
     */
    public function addLoyaltyPoints($amount, $description, $source = 'manual', $reference = null, $expiresAt = null)
    {
        $currentBalance = $this->loyalty_points_balance;
        $newBalance = $currentBalance + $amount;
        
        return $this->loyaltyPoints()->create([
            'amount' => $amount,
            'type' => $amount > 0 ? 'earned' : 'redeemed',
            'source' => $source,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'balance_after' => $newBalance,
            'expires_at' => $expiresAt,
        ]);
    }
    
    /**
     * Έλεγχος αν ο χρήστης έχει αρκετούς πόντους
     */
    public function hasEnoughLoyaltyPoints($amount)
    {
        return $this->loyalty_points_balance >= $amount;
    }
    
    /**
     * Get the display name for how the user found us
     */
    public function getHowFoundUsDisplayAttribute()
    {
        $options = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google' => 'Google Search',
            'friend' => 'Friend Referral',
            'member' => 'Member Referral',
            'website' => 'Website',
            'walk_in' => 'Walk In',
            'flyer' => 'Flyer/Poster',
            'event' => 'Event',
            'other' => 'Other'
        ];
        
        return $options[$this->found_us_via] ?? $this->found_us_via;
    }
    
    /**
     * Check if user was referred by someone
     */
    public function wasReferred()
    {
        return $this->referrer_id !== null || 
               in_array($this->found_us_via, ['friend', 'member']);
    }
    
    /**
     * Get referral statistics
     */
    public function getReferralStats()
    {
        return [
            'total_referrals' => $this->referredUsers()->count(),
            'active_referrals' => $this->referredUsers()->where('status', 'active')->count(),
            'validated_referrals' => $this->referredUsers()->where('referral_validated', true)->count(),
        ];
    }
}
