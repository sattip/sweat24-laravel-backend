<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;

class ExercisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate with foreign key handling
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Exercise::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            Exercise::query()->delete();
        }

        $exercises = $this->getExercises();

        foreach ($exercises as $exercise) {
            Exercise::create($exercise);
        }

        $this->command->info('Successfully seeded ' . count($exercises) . ' exercises!');
    }

    private function getExercises(): array
    {
        return [
            // CHEST (12 exercises)
            ['name_en' => 'Barbell Bench Press', 'name_gr' => 'Πιεστήρι με Μπάρα', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Incline Barbell Bench Press', 'name_gr' => 'Πιεστήρι Κεκλιμένο με Μπάρα', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Decline Barbell Bench Press', 'name_gr' => 'Πιεστήρι Αρνητικό με Μπάρα', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Dumbbell Bench Press', 'name_gr' => 'Πιεστήρι με Αλτήρες', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['dumbbells', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Incline Dumbbell Press', 'name_gr' => 'Κεκλιμένο Πιεστήρι με Αλτήρες', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['dumbbells', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Dumbbell Flyes', 'name_gr' => 'Πεταλούδες με Αλτήρες', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['dumbbells', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Crossover', 'name_gr' => 'Σταυρός Καλωδίων', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Push-Ups', 'name_gr' => 'Κάμψεις', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Chest Dips', 'name_gr' => 'Πτώσεις Στήθους', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Pec Deck Machine', 'name_gr' => 'Μηχάνημα Πεταλούδες', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Chest Press Machine', 'name_gr' => 'Μηχάνημα Πιεστηρίου Στήθους', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Fly', 'name_gr' => 'Πεταλούδες με Καλώδιο', 'muscle_group' => 'chest', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],

            // BACK (20 exercises)
            ['name_en' => 'Deadlift', 'name_gr' => 'Ανάσταση Νεκρού', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Bent Over Barbell Row', 'name_gr' => 'Κωπηλασία με Μπάρα Κεκαμμένος', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Pull-Ups', 'name_gr' => 'Ελκυστήρες', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Chin-Ups', 'name_gr' => 'Ελκυστήρες Αντίστροφοι', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Lat Pulldown', 'name_gr' => 'Έλξη Πλάτης Καθιστή', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Seated Cable Row', 'name_gr' => 'Κωπηλασία Καθιστή με Καλώδιο', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'One-Arm Dumbbell Row', 'name_gr' => 'Κωπηλασία με Αλτήρα Ένα Χέρι', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'T-Bar Row', 'name_gr' => 'Κωπηλασία T-Bar', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Face Pulls', 'name_gr' => 'Έλξεις Προσώπου', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Romanian Deadlift', 'name_gr' => 'Ρουμανική Ανάσταση', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Hyperextensions', 'name_gr' => 'Υπερεκτάσεις Κάτω Πλάτης', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'TRX Row', 'name_gr' => 'Κωπηλασία TRX', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['trx'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Pendlay Row', 'name_gr' => 'Κωπηλασία Pendlay', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Landmine Row', 'name_gr' => 'Κωπηλασία Landmine', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Seal Row', 'name_gr' => 'Κωπηλασία Φώκιας', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Chest-Supported Row', 'name_gr' => 'Κωπηλασία με Στήριξη Στήθους', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['dumbbells', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Single Arm Cable Row', 'name_gr' => 'Κωπηλασία με Καλώδιο Ένα Χέρι', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Muscle-Ups', 'name_gr' => 'Muscle-Ups', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Hex Bar Deadlift', 'name_gr' => 'Ανάσταση με Hex Bar', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Deficit Deadlift', 'name_gr' => 'Ανάσταση με Έλλειμμα', 'muscle_group' => 'back', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],

            // SHOULDERS (12 exercises)
            ['name_en' => 'Overhead Press', 'name_gr' => 'Πιεστήρι Ώμων Όρθιο', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Dumbbell Shoulder Press', 'name_gr' => 'Πιεστήρι Ώμων με Αλτήρες', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Arnold Press', 'name_gr' => 'Πιεστήρι Arnold', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Lateral Raises', 'name_gr' => 'Ανυψώσεις Πλάγιες', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Front Raises', 'name_gr' => 'Ανυψώσεις Εμπρός', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Rear Delt Flyes', 'name_gr' => 'Πεταλούδες Οπίσθιων Δελτοειδών', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Lateral Raises', 'name_gr' => 'Πλάγιες Ανυψώσεις με Καλώδιο', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Upright Row', 'name_gr' => 'Κωπηλασία Όρθια', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Handstand Push-Ups', 'name_gr' => 'Κάμψεις σε Χειροστασία', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Landmine Press', 'name_gr' => 'Πιεστήριο Landmine', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Resistance Band Pull-Apart', 'name_gr' => 'Τράβηγμα Λάστιχου', 'muscle_group' => 'shoulders', 'category' => 'strength', 'equipment' => ['resistance_band'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Log Press', 'name_gr' => 'Πιεστήριο Κορμού', 'muscle_group' => 'shoulders', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'advanced'],

            // TRAPEZIUS (4 exercises)
            ['name_en' => 'Barbell Shrugs', 'name_gr' => 'Σρούγκς με Μπάρα', 'muscle_group' => 'trapezius', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Dumbbell Shrugs', 'name_gr' => 'Σρούγκς με Αλτήρες', 'muscle_group' => 'trapezius', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Shrugs', 'name_gr' => 'Σρούγκς με Καλώδιο', 'muscle_group' => 'trapezius', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Farmer\'s Walk', 'name_gr' => 'Περπάτημα Αγρότη', 'muscle_group' => 'trapezius', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],

            // BICEPS (8 exercises)
            ['name_en' => 'Barbell Bicep Curl', 'name_gr' => 'Μπάρφιξ Δικεφάλου με Μπάρα', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Dumbbell Bicep Curl', 'name_gr' => 'Μπάρφιξ Δικεφάλου με Αλτήρες', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Hammer Curl', 'name_gr' => 'Σφυρήλατο Μπάρφιξ', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Preacher Curl', 'name_gr' => 'Μπάρφιξ Scott', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Bicep Curl', 'name_gr' => 'Μπάρφιξ με Καλώδιο', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Concentration Curl', 'name_gr' => 'Μπάρφιξ Συγκέντρωσης', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Incline Dumbbell Curl', 'name_gr' => 'Κεκλιμένο Μπάρφιξ με Αλτήρες', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['dumbbells', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Zottman Curl', 'name_gr' => 'Μπάρφιξ Zottman', 'muscle_group' => 'biceps', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],

            // TRICEPS (8 exercises)
            ['name_en' => 'Close-Grip Bench Press', 'name_gr' => 'Πιεστήρι Στενή Λαβή', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Tricep Dips', 'name_gr' => 'Πτώσεις Τρικεφάλου', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Overhead Tricep Extension', 'name_gr' => 'Έκταση Τρικεφάλου Πάνω από το Κεφάλι', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Tricep Pushdown', 'name_gr' => 'Πίεση Κάτω Τρικεφάλου', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Skull Crushers', 'name_gr' => 'Θραύστες Κρανίου', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Diamond Push-Ups', 'name_gr' => 'Κάμψεις Διαμάντι', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Rope Tricep Extension', 'name_gr' => 'Έκταση Τρικεφάλου με Σχοινί', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Bench Dips', 'name_gr' => 'Πτώσεις σε Παγκάκι', 'muscle_group' => 'triceps', 'category' => 'strength', 'equipment' => ['bench'], 'difficulty_level' => 'beginner'],

            // FOREARMS (4 exercises)
            ['name_en' => 'Wrist Curls', 'name_gr' => 'Μπάρφιξ Καρπών', 'muscle_group' => 'forearms', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Reverse Wrist Curls', 'name_gr' => 'Αντίστροφο Μπάρφιξ Καρπών', 'muscle_group' => 'forearms', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Plate Pinch', 'name_gr' => 'Τσίμπημα Δίσκου', 'muscle_group' => 'forearms', 'category' => 'strength', 'equipment' => ['equipment'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Dead Hang', 'name_gr' => 'Κρέμασμα Νεκρό', 'muscle_group' => 'forearms', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],

            // QUADS (15 exercises)
            ['name_en' => 'Barbell Back Squat', 'name_gr' => 'Κάθισμα με Μπάρα Πίσω', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Front Squat', 'name_gr' => 'Κάθισμα με Μπάρα Μπροστά', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Leg Press', 'name_gr' => 'Πιεστήριο Ποδιών', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Leg Extension', 'name_gr' => 'Έκταση Ποδιών', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Bulgarian Split Squat', 'name_gr' => 'Βουλγαρικό Split Squat', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Goblet Squat', 'name_gr' => 'Κάθισμα Goblet', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Lunges', 'name_gr' => 'Ρόγες', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['dumbbells'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Smith Machine Squat', 'name_gr' => 'Κάθισμα Smith Machine', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Hack Squat', 'name_gr' => 'Hack Squat', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'TRX Lunge', 'name_gr' => 'Ρόγα TRX', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['trx'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Zercher Squat', 'name_gr' => 'Κάθισμα Zercher', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Overhead Squat', 'name_gr' => 'Κάθισμα Πάνω από Κεφάλι', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Safety Bar Squat', 'name_gr' => 'Κάθισμα με Safety Bar', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Paused Squat', 'name_gr' => 'Κάθισμα με Παύση', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Pistol Squats', 'name_gr' => 'Καθίσματα Πιστόλι', 'muscle_group' => 'quads', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],

            // HAMSTRINGS (7 exercises)
            ['name_en' => 'Lying Leg Curl', 'name_gr' => 'Κάμψη Ποδιών Ξαπλωτοί', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Seated Leg Curl', 'name_gr' => 'Κάμψη Ποδιών Καθιστοί', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Romanian Deadlift', 'name_gr' => 'Ρουμανική Ανάσταση', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Stiff-Leg Deadlift', 'name_gr' => 'Ανάσταση Τεντωμένα Πόδια', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Nordic Hamstring Curl', 'name_gr' => 'Σκανδιναβική Κάμψη Οπίσθιων', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'TRX Hamstring Curl', 'name_gr' => 'Κάμψη Οπισθίων TRX', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['trx'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Good Mornings', 'name_gr' => 'Καλημέρες', 'muscle_group' => 'hamstrings', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],

            // GLUTES (8 exercises)
            ['name_en' => 'Hip Thrust', 'name_gr' => 'Ώθηση Ισχίων', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['barbell', 'bench'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Glute Bridge', 'name_gr' => 'Γέφυρα Γλουτών', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cable Pull-Through', 'name_gr' => 'Έλξη με Καλώδιο Διαμπερής', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Sumo Deadlift', 'name_gr' => 'Ανάσταση Sumo', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Hip Abduction Machine', 'name_gr' => 'Μηχάνημα Απαγωγών', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Resistance Band Lateral Walk', 'name_gr' => 'Πλάγιο Περπάτημα με Λάστιχο', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['resistance_band'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Single Leg Glute Bridge', 'name_gr' => 'Γέφυρα Γλουτών Ένα Πόδι', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Donkey Kicks', 'name_gr' => 'Κλωτσιές Γαϊδάρου', 'muscle_group' => 'glutes', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],

            // ADDUCTORS (2 exercises)
            ['name_en' => 'Hip Adduction Machine', 'name_gr' => 'Μηχάνημα Προσαγωγών', 'muscle_group' => 'adductors', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Copenhagen Plank', 'name_gr' => 'Σανίδα Κοπεγχάγης', 'muscle_group' => 'adductors', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],

            // CALVES (3 exercises)
            ['name_en' => 'Standing Calf Raise', 'name_gr' => 'Ανύψωση Γάμπας Όρθιος', 'muscle_group' => 'calves', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Seated Calf Raise', 'name_gr' => 'Ανύψωση Γάμπας Καθιστός', 'muscle_group' => 'calves', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Calf Raise on Leg Press', 'name_gr' => 'Ανύψωση Γάμπας στο Πιεστήριο', 'muscle_group' => 'calves', 'category' => 'strength', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],

            // ABS (15 exercises)
            ['name_en' => 'Crunches', 'name_gr' => 'Κοιλιακοί', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Plank', 'name_gr' => 'Σανίδα', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Side Plank', 'name_gr' => 'Πλαϊνή Σανίδα', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Russian Twist', 'name_gr' => 'Ρωσικές Στροφές', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Hanging Leg Raises', 'name_gr' => 'Ανυψώσεις Ποδιών Κρεμαστός', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Cable Crunch', 'name_gr' => 'Κοιλιακοί με Καλώδιο', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['cable'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Bicycle Crunches', 'name_gr' => 'Κοιλιακοί Ποδήλατο', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Mountain Climbers', 'name_gr' => 'Ορειβάτες', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Ab Wheel Rollout', 'name_gr' => 'Τροχός Κοιλιακών', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['equipment'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Sit-Ups', 'name_gr' => 'Sit-Ups', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'TRX Pike', 'name_gr' => 'TRX Pike', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['trx'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Cable Woodchop', 'name_gr' => 'Ξυλοκόπος με Καλώδιο', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['cable'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'L-Sit', 'name_gr' => 'L-Sit', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Hollow Body Hold', 'name_gr' => 'Κράτημα Κοίλου Σώματος', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'V-Ups', 'name_gr' => 'V-Ups', 'muscle_group' => 'abs', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],

            // LOWER BACK (3 exercises)
            ['name_en' => 'Superman', 'name_gr' => 'Σούπερμαν', 'muscle_group' => 'lower_back', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Back Extension', 'name_gr' => 'Υπερεκτάσεις Κάτω Πλάτης', 'muscle_group' => 'lower_back', 'category' => 'core', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Bird Dog', 'name_gr' => 'Σκύλος Πουλί', 'muscle_group' => 'lower_back', 'category' => 'core', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],

            // CARDIO (12 exercises)
            ['name_en' => 'Running', 'name_gr' => 'Τρέξιμο', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Treadmill', 'name_gr' => 'Διάδρομος', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Rowing Machine', 'name_gr' => 'Μηχάνημα Κωπηλασίας', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Assault Bike', 'name_gr' => 'Assault Bike', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Jump Rope', 'name_gr' => 'Σχοινάκι', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['equipment'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Elliptical', 'name_gr' => 'Ελλειπτικό Μηχάνημα', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cycling', 'name_gr' => 'Ποδηλασία', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Stair Climber', 'name_gr' => 'Ανάβαση Σκάλας', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'High Knees', 'name_gr' => 'Ψηλά Γόνατα', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Sprints', 'name_gr' => 'Σπριντ', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Swimming', 'name_gr' => 'Κολύμβηση', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Ski Erg', 'name_gr' => 'Ski Erg', 'muscle_group' => 'cardio', 'category' => 'cardio', 'equipment' => ['machine'], 'difficulty_level' => 'intermediate'],

            // BALANCE (6 exercises)
            ['name_en' => 'Single Leg Balance', 'name_gr' => 'Ισορροπία Ένα Πόδι', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Bosu Ball Squats', 'name_gr' => 'Καθίσματα σε Bosu Ball', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['bosu_ball'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Single Leg Deadlift', 'name_gr' => 'Ανάσταση Ένα Πόδι', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Balance Board Standing', 'name_gr' => 'Στάση σε Balance Board', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Bosu Ball Plank', 'name_gr' => 'Σανίδα σε Bosu Ball', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['bosu_ball'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Single Leg Romanian Deadlift', 'name_gr' => 'Ρουμανική Ένα Πόδι', 'muscle_group' => 'balance', 'category' => 'balance', 'equipment' => ['dumbbells'], 'difficulty_level' => 'advanced'],

            // MOBILITY (10 exercises)
            ['name_en' => 'Hip Flexor Stretch', 'name_gr' => 'Τέντωμα Καμπτήρων Ισχίου', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Hamstring Stretch', 'name_gr' => 'Τέντωμα Οπισθίων', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Shoulder Mobility Drill', 'name_gr' => 'Άσκηση Κινητικότητας Ώμων', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['resistance_band'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cat-Cow Stretch', 'name_gr' => 'Τέντωμα Γάτας-Αγελάδας', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Thread the Needle', 'name_gr' => 'Βελόνισμα', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'World\'s Greatest Stretch', 'name_gr' => 'Το Καλύτερο Τέντωμα του Κόσμου', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Foam Rolling', 'name_gr' => 'Foam Rolling', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['equipment'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Ankle Circles', 'name_gr' => 'Κυκλικές Κινήσεις Αστραγάλου', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Child\'s Pose', 'name_gr' => 'Στάση Παιδιού', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Scorpion Stretch', 'name_gr' => 'Τέντωμα Σκορπιού', 'muscle_group' => 'mobility', 'category' => 'mobility', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],

            // COGNITIVE (5 exercises)
            ['name_en' => 'Agility Ladder Drills', 'name_gr' => 'Ασκήσεις Σκάλας Ευελιξίας', 'muscle_group' => 'cognitive', 'category' => 'cognitive', 'equipment' => ['equipment'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Cone Drills', 'name_gr' => 'Ασκήσεις με Κώνους', 'muscle_group' => 'cognitive', 'category' => 'cognitive', 'equipment' => ['equipment'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Reaction Ball Training', 'name_gr' => 'Προπόνηση με Μπάλα Αντίδρασης', 'muscle_group' => 'cognitive', 'category' => 'cognitive', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Boxing Drills', 'name_gr' => 'Ασκήσεις Πυγμαχίας', 'muscle_group' => 'cognitive', 'category' => 'cognitive', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Mirror Drills', 'name_gr' => 'Ασκήσεις Καθρέφτη', 'muscle_group' => 'cognitive', 'category' => 'cognitive', 'equipment' => ['bodyweight'], 'difficulty_level' => 'beginner'],

            // FUNCTIONAL/TOTAL BODY (25 exercises)
            ['name_en' => 'Burpees', 'name_gr' => 'Μπέρπις', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Kettlebell Swing', 'name_gr' => 'Κούνημα Kettlebell', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['kettlebell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Turkish Get-Up', 'name_gr' => 'Τούρκικο Σήκωμα', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['kettlebell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Medicine Ball Slam', 'name_gr' => 'Χτύπημα Μπάλας Ιατρικής', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['medicine_ball'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Battle Ropes', 'name_gr' => 'Σχοινιά Μάχης', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Box Jumps', 'name_gr' => 'Άλματα Κιβωτίου', 'muscle_group' => 'power', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Sled Push', 'name_gr' => 'Σπρώξιμο Έλκηθρου', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Tire Flip', 'name_gr' => 'Αναστροφή Λάστιχου', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Clean and Jerk', 'name_gr' => 'Ανάρπαγη και Ώθηση', 'muscle_group' => 'total_body', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Snatch', 'name_gr' => 'Σπασμένη Ανάρπαγη', 'muscle_group' => 'total_body', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Thruster', 'name_gr' => 'Thruster', 'muscle_group' => 'total_body', 'category' => 'strength', 'equipment' => ['barbell'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Wall Balls', 'name_gr' => 'Μπάλες Τοίχου', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['medicine_ball'], 'difficulty_level' => 'beginner'],
            ['name_en' => 'Dumbbell Thrusters', 'name_gr' => 'Thruster με Αλτήρες', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Dumbbell Clean', 'name_gr' => 'Καθαρισμός με Αλτήρες', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Single Arm Dumbbell Snatch', 'name_gr' => 'Ανάρπαγη με Αλτήρα Ένα Χέρι', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Renegade Rows', 'name_gr' => 'Κωπηλασία Renegade', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Man Makers', 'name_gr' => 'Man Makers', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Devil Press', 'name_gr' => 'Devil Press', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['dumbbells'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'Sandbag Carry', 'name_gr' => 'Μεταφορά Σάκου Άμμου', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Prowler Push', 'name_gr' => 'Σπρώξιμο Prowler', 'muscle_group' => 'total_body', 'category' => 'functional', 'equipment' => ['equipment'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Broad Jumps', 'name_gr' => 'Άλματα σε Μάκρος', 'muscle_group' => 'power', 'category' => 'functional', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Jump Squats', 'name_gr' => 'Καθίσματα με Άλμα', 'muscle_group' => 'power', 'category' => 'functional', 'equipment' => ['bodyweight'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Clap Push-Ups', 'name_gr' => 'Κάμψεις με Χειροκρότημα', 'muscle_group' => 'power', 'category' => 'functional', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
            ['name_en' => 'TRX Push-Up', 'name_gr' => 'Κάμψεις TRX', 'muscle_group' => 'chest', 'category' => 'functional', 'equipment' => ['trx'], 'difficulty_level' => 'intermediate'],
            ['name_en' => 'Tuck Planche', 'name_gr' => 'Tuck Planche', 'muscle_group' => 'total_body', 'category' => 'strength', 'equipment' => ['bodyweight'], 'difficulty_level' => 'advanced'],
        ];
    }
}
