<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeManual extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employee_manual';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'category',
        'is_active',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Available categories
     */
    const CATEGORIES = [
        'general' => 'Γενικές Πληροφορίες',
        'rules' => 'Κανόνες',
        'procedures' => 'Διαδικασίες',
        'safety' => 'Ασφάλεια',
        'customer_service' => 'Εξυπηρέτηση Πελατών',
        'equipment' => 'Εξοπλισμός',
        'faq' => 'Συχνές Ερωτήσεις'
    ];

    /**
     * Get the category label
     *
     * @return string
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
