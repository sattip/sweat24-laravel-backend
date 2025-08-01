<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalHistory extends Model
{
    protected $fillable = [
        'user_id',
        'medical_conditions',
        'current_health_problems',
        'prescribed_medications',
        'smoking',
        'physical_activity',
        'emergency_contact',
        'liability_declaration_accepted',
        'submitted_at'
    ];

    protected $casts = [
        'medical_conditions' => 'array',
        'current_health_problems' => 'array',
        'prescribed_medications' => 'array',
        'smoking' => 'array',
        'physical_activity' => 'array',
        'emergency_contact' => 'array',
        'liability_declaration_accepted' => 'boolean',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the medical history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the latest medical history for a user.
     */
    public static function getLatestForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    /**
     * Check if user has any contraindications for EMS training.
     */
    public function hasEmsContraindications(): bool
    {
        $conditions = $this->medical_conditions ?? [];
        
        // Absolute contraindications for EMS (based on the form)
        $absoluteContraindications = [
            'Βηματοδότης',
            'Εγκυμοσύνη',
            'Πυρετός, οξείες βακτηριακές ή ιογενείς λοιμώξεις',
            'Θρόμβωση / Θρομβοφλεβίτιδα',
            'Stent ή Bypass (εντός τελευταίων 6 μηνών)',
            'Αρτηριοσκλήρωση σε προχωρημένο στάδιο',
            'Υψηλή αρτηριακή πίεση (χωρίς ιατρικό έλεγχο)',
            'Αιμορραγικές διαταραχές',
            'Νεοπλασματικές ασθένειες (όγκοι – καρκίνος)',
            'Οξεία αρθρίτιδα',
            'Νευρολογικές ασθένειες',
            'Προοδευτική μυϊκή δυστροφία',
            'Κήλες κοιλιακού τοιχώματος ή βουβωνοκήλες',
            'Λεμφοίδημα'
        ];

        foreach ($absoluteContraindications as $condition) {
            if (isset($conditions[$condition]) && $conditions[$condition]['has_condition'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all medical conditions that are marked as "yes".
     */
    public function getActiveConditions(): array
    {
        $conditions = $this->medical_conditions ?? [];
        $activeConditions = [];

        foreach ($conditions as $conditionName => $conditionData) {
            if (isset($conditionData['has_condition']) && $conditionData['has_condition'] === true) {
                $activeConditions[] = [
                    'condition' => $conditionName,
                    'year_of_onset' => $conditionData['year_of_onset'] ?? null,
                    'details' => $conditionData['details'] ?? null
                ];
            }
        }

        return $activeConditions;
    }
}
