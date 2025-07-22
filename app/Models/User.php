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
}
