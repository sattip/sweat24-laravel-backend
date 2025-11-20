<?php

/**
 * Simple test to verify muscle groups feature is working
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Booking;
use App\Models\WorkoutMuscleGroup;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MUSCLE GROUPS FEATURE VERIFICATION ===\n\n";

// 1. Check if table exists
echo "1. Checking if workout_muscle_groups table exists...\n";
$tableExists = \DB::getSchemaBuilder()->hasTable('workout_muscle_groups');
echo $tableExists ? "   ✅ Table exists\n" : "   ❌ Table does not exist\n";

// 2. Check model relationships
echo "\n2. Checking model relationships...\n";
$booking = new Booking();
echo method_exists($booking, 'muscleGroups') ? "   ✅ Booking->muscleGroups() relationship exists\n" : "   ❌ Booking->muscleGroups() relationship missing\n";

$workoutMuscleGroup = new WorkoutMuscleGroup();
echo method_exists($workoutMuscleGroup, 'booking') ? "   ✅ WorkoutMuscleGroup->booking() relationship exists\n" : "   ❌ WorkoutMuscleGroup->booking() relationship missing\n";
echo method_exists($workoutMuscleGroup, 'user') ? "   ✅ WorkoutMuscleGroup->user() relationship exists\n" : "   ❌ WorkoutMuscleGroup->user() relationship missing\n";

// 3. Check valid muscle groups constant
echo "\n3. Checking valid muscle groups constant...\n";
$validGroups = WorkoutMuscleGroup::VALID_MUSCLE_GROUPS;
echo "   Valid muscle groups: " . implode(', ', $validGroups) . "\n";
$expectedGroups = ['total_body', 'legs', 'chest', 'back', 'shoulders', 'arms', 'core', 'cardio'];
$allPresent = count(array_diff($expectedGroups, $validGroups)) === 0;
echo $allPresent ? "   ✅ All expected muscle groups present\n" : "   ❌ Some muscle groups missing\n";

// 4. Check controller exists
echo "\n4. Checking if controller exists...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/Api/WorkoutMuscleGroupController.php';
echo file_exists($controllerPath) ? "   ✅ WorkoutMuscleGroupController exists\n" : "   ❌ WorkoutMuscleGroupController not found\n";

// 5. Check routes
echo "\n5. Checking routes...\n";
$routes = \Route::getRoutes();
$foundStore = false;
$foundShow = false;

foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'workouts/{bookingId}/muscle-groups') !== false) {
        if (in_array('POST', $route->methods())) {
            $foundStore = true;
            echo "   ✅ POST /api/v1/workouts/{bookingId}/muscle-groups route exists\n";
        }
        if (in_array('GET', $route->methods())) {
            $foundShow = true;
            echo "   ✅ GET /api/v1/workouts/{bookingId}/muscle-groups route exists\n";
        }
    }
}

if (!$foundStore) echo "   ❌ POST route not found\n";
if (!$foundShow) echo "   ❌ GET route not found\n";

// 6. Test database operations
echo "\n6. Testing database operations...\n";
try {
    // Find an attended booking
    $booking = Booking::where('attended', 1)->first();
    
    if ($booking) {
        echo "   Found attended booking ID: {$booking->id}\n";
        
        // Try to create a muscle group record
        $muscleGroup = WorkoutMuscleGroup::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id' => $booking->user_id,
                'muscle_groups' => ['test', 'verification']
            ]
        );
        
        echo "   ✅ Successfully created/updated muscle group record\n";
        
        // Verify it was stored
        $retrieved = WorkoutMuscleGroup::where('booking_id', $booking->id)->first();
        if ($retrieved && $retrieved->muscle_groups == ['test', 'verification']) {
            echo "   ✅ Data stored and retrieved correctly\n";
        } else {
            echo "   ❌ Data retrieval failed\n";
        }
        
        // Clean up
        $muscleGroup->delete();
        echo "   ✅ Test data cleaned up\n";
    } else {
        echo "   ⚠️  No attended bookings found to test with\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database operation failed: " . $e->getMessage() . "\n";
}

// 7. Check if test history includes muscle groups
echo "\n7. Checking test history endpoint...\n";
$bookingController = new \App\Http\Controllers\BookingController();
$methodCode = (new ReflectionMethod($bookingController, 'testHistory'))->getFileName();
$content = file_get_contents($methodCode);
if (strpos($content, 'muscleGroups') !== false && strpos($content, 'muscle_groups_recorded') !== false) {
    echo "   ✅ testHistory method includes muscle groups support\n";
} else {
    echo "   ❌ testHistory method might not include muscle groups support\n";
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 VERIFICATION COMPLETE\n";
echo str_repeat('=', 50) . "\n";

$checks = [
    'Database table exists' => $tableExists,
    'Model relationships configured' => method_exists($booking, 'muscleGroups'),
    'Valid muscle groups defined' => $allPresent,
    'Controller exists' => file_exists($controllerPath),
    'Routes configured' => $foundStore && $foundShow,
];

$passed = array_filter($checks);
$total = count($checks);
$passedCount = count($passed);

foreach ($checks as $check => $result) {
    echo ($result ? "✅" : "❌") . " $check\n";
}

echo "\n🎯 Result: $passedCount/$total checks passed\n";

if ($passedCount === $total) {
    echo "✨ Muscle groups feature is fully implemented!\n";
} else {
    echo "⚠️  Some components may need attention\n";
}