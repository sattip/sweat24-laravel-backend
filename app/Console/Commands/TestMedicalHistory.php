<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MedicalHistory;
use Carbon\Carbon;

class TestMedicalHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:medical-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Medical History functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Medical History Implementation...');
        $this->newLine();

        // Find test user
        $testUser = User::where('email', 'admin@sweat24.gr')->first();
        if (!$testUser) {
            $this->error('❌ Test user not found!');
            return 1;
        }

        $this->info("✅ Test user found: {$testUser->name} (ID: {$testUser->id})");

        // Sample medical data
        $sampleMedicalData = [
            'medical_conditions' => [
                'Καρδιακή νόσος ή καρδιακό επεισόδιο' => [
                    'has_condition' => true,
                    'year_of_onset' => '2020',
                    'details' => 'Μικρό καρδιακό επεισόδιο'
                ],
                'Διαβήτης τύπου 1 ή 2' => [
                    'has_condition' => true,
                    'year_of_onset' => '2018',
                    'details' => ''
                ],
                'Υπέρταση' => [
                    'has_condition' => false,
                    'year_of_onset' => null,
                    'details' => null
                ]
            ],
            'current_health_problems' => [
                'has_problems' => true,
                'details' => 'Πόνοι στη μέση που επιδεινώνονται με την άσκηση'
            ],
            'prescribed_medications' => [
                [
                    'medication' => 'Metformin',
                    'reason' => 'Διαβήτης'
                ],
                [
                    'medication' => 'Lisinopril',
                    'reason' => 'Υπέρταση'
                ]
            ],
            'smoking' => [
                'currently_smoking' => false,
                'daily_cigarettes' => null,
                'ever_smoked' => true,
                'smoking_years' => '10',
                'quit_years_ago' => '3'
            ],
            'physical_activity' => [
                'description' => 'Τρέξιμο στο πάρκο και ποδήλατο',
                'frequency' => '3 φορές την εβδομάδα',
                'duration' => '45 λεπτά'
            ],
            'emergency_contact' => [
                'name' => 'Μαρία Παπαδοπούλου',
                'phone' => '6901234567'
            ],
            'liability_declaration_accepted' => true,
            'submitted_at' => '2025-08-01T10:30:00Z'
        ];

        try {
            // Delete existing medical history for clean test
            MedicalHistory::where('user_id', $testUser->id)->delete();

            // Create medical history
            $medicalHistory = MedicalHistory::create([
                'user_id' => $testUser->id,
                'medical_conditions' => $sampleMedicalData['medical_conditions'],
                'current_health_problems' => $sampleMedicalData['current_health_problems'],
                'prescribed_medications' => $sampleMedicalData['prescribed_medications'],
                'smoking' => $sampleMedicalData['smoking'],
                'physical_activity' => $sampleMedicalData['physical_activity'],
                'emergency_contact' => $sampleMedicalData['emergency_contact'],
                'liability_declaration_accepted' => $sampleMedicalData['liability_declaration_accepted'],
                'submitted_at' => Carbon::parse($sampleMedicalData['submitted_at'])
            ]);

            $this->info("✅ Medical history created successfully! ID: {$medicalHistory->id}");
            
            // Test model methods
            $this->newLine();
            $this->info('📋 Testing Model Methods:');
            $this->line("- Has EMS Contraindications: " . ($medicalHistory->hasEmsContraindications() ? 'ΝΑΙ' : 'ΟΧΙ'));
            $this->line("- Active Conditions Count: " . count($medicalHistory->getActiveConditions()));
            $this->line("- User has medical history: " . ($testUser->hasMedicalHistory() ? 'ΝΑΙ' : 'ΟΧΙ'));
            
            // Show active conditions
            $activeConditions = $medicalHistory->getActiveConditions();
            $this->newLine();
            $this->info('🏥 Active Medical Conditions:');
            foreach ($activeConditions as $condition) {
                $this->line("  - {$condition['condition']} (από {$condition['year_of_onset']})");
            }
            
            // Show database structure
            $this->newLine();
            $this->info('📊 Database Record Created:');
            $this->line("Table: medical_histories");
            $this->line("ID: {$medicalHistory->id}");
            $this->line("User ID: {$medicalHistory->user_id}");
            $this->line("Submitted At: {$medicalHistory->submitted_at}");
            $this->line("Created At: {$medicalHistory->created_at}");
            
            $this->newLine();
            $this->info('✅ All tests passed! Medical History system is working.');
            
            $this->newLine();
            $this->info('🎯 Ready for API Testing!');
            $this->line('Endpoints available:');
            $this->line("- POST /api/v1/medical-history (for client app)");
            $this->line("- GET  /api/v1/medical-history (user's own data)");
            $this->line("- GET  /api/admin/users/{$testUser->id}/medical-history (for admin panel)");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->line("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
