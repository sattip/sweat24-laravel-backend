<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    // Notification type constants
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';
    const TYPE_OFFER = 'offer';           // Προσφορά
    const TYPE_PARTY_EVENT = 'party_event'; // Πάρτι/Εκδήλωση
    const TYPE_ORDER_STATUS = 'order_status'; // Κατάσταση Παραγγελίας
    
    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'channels',
        'filters',
        'created_by',
        'scheduled_at',
        'sent_at',
        'status',
        'total_recipients',
        'delivered_count',
        'read_count',
    ];

    protected $casts = [
        'channels' => 'array',
        'filters' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get all available notification types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INFO => 'Πληροφορία',
            self::TYPE_WARNING => 'Προειδοποίηση',
            self::TYPE_SUCCESS => 'Επιτυχία',
            self::TYPE_ERROR => 'Σφάλμα',
            self::TYPE_OFFER => 'Προσφορά',
            self::TYPE_PARTY_EVENT => 'Πάρτι/Εκδήλωση',
            self::TYPE_ORDER_STATUS => 'Κατάσταση Παραγγελίας',
        ];
    }

    /**
     * Get notification type icons
     */
    public static function getTypeIcons(): array
    {
        return [
            self::TYPE_INFO => 'info-circle',
            self::TYPE_WARNING => 'exclamation-triangle',
            self::TYPE_SUCCESS => 'check-circle',
            self::TYPE_ERROR => 'times-circle',
            self::TYPE_OFFER => 'tag',
            self::TYPE_PARTY_EVENT => 'calendar-star',
            self::TYPE_ORDER_STATUS => 'shopping-bag',
        ];
    }

    /**
     * Get notification type colors
     */
    public static function getTypeColors(): array
    {
        return [
            self::TYPE_INFO => 'blue',
            self::TYPE_WARNING => 'yellow',
            self::TYPE_SUCCESS => 'green',
            self::TYPE_ERROR => 'red',
            self::TYPE_OFFER => 'purple',
            self::TYPE_PARTY_EVENT => 'pink',
            self::TYPE_ORDER_STATUS => 'orange',
        ];
    }

    /**
     * Get the user who created the notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the recipients of the notification.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * Get recipients with a specific status.
     */
    public function deliveredRecipients(): HasMany
    {
        return $this->recipients()->where('delivery_status', 'delivered');
    }

    /**
     * Get recipients who have read the notification.
     */
    public function readRecipients(): HasMany
    {
        return $this->recipients()->whereNotNull('read_at');
    }

    /**
     * Scope for pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'draft')->orWhere('status', 'scheduled');
    }

    /**
     * Scope for sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope by notification type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if notification is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at !== null;
    }

    /**
     * Check if notification is an offer
     */
    public function isOffer(): bool
    {
        return $this->type === self::TYPE_OFFER;
    }

    /**
     * Check if notification is a party/event
     */
    public function isPartyEvent(): bool
    {
        return $this->type === self::TYPE_PARTY_EVENT;
    }

    /**
     * Get the notification type label
     */
    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? 'Άγνωστος';
    }

    /**
     * Get the notification type icon
     */
    public function getTypeIcon(): string
    {
        return self::getTypeIcons()[$this->type] ?? 'bell';
    }

    /**
     * Get the notification type color
     */
    public function getTypeColor(): string
    {
        return self::getTypeColors()[$this->type] ?? 'gray';
    }
}
