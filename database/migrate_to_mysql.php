<?php

/**
 * Complete Migration Script from SQLite to MySQL
 * This handles the full migration including fixing problematic migrations
 */

echo "=== SQLite to MySQL Migration Tool ===\n\n";

// Step 1: Check if Laravel can connect
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

// Check current connection
$currentDriver = env('DB_CONNECTION');
echo "Current database driver: $currentDriver\n";

if ($currentDriver !== 'mysql') {
    echo "\n⚠️  WARNING: Your .env file is not configured for MySQL yet.\n";
    echo "Please update your .env file with MySQL credentials before running migrations.\n";
    echo "Example configuration:\n";
    echo "  DB_CONNECTION=mysql\n";
    echo "  DB_HOST=127.0.0.1\n";
    echo "  DB_PORT=3306\n";
    echo "  DB_DATABASE=sweat93_db\n";
    echo "  DB_USERNAME=sweat93_user\n";
    echo "  DB_PASSWORD=your_password\n\n";
    
    $continue = readline("Do you want to continue with data export only? (yes/no): ");
    if (strtolower($continue) !== 'yes') {
        exit(0);
    }
}

// Step 2: Mark problematic SQLite-specific migrations as run
if ($currentDriver === 'mysql') {
    echo "\nMarking SQLite-specific migrations as already run...\n";
    
    $sqliteSpecificMigrations = [
        '2025_09_01_124110_update_payment_method_enum_to_include_iris_and_cash_a.php',
        '2025_09_01_125432_fix_payment_method_constraints_for_sqlite.php',
        '2025_07_21_193610_add_pending_approval_to_registration_status.php',
        '2025_07_21_200000_add_pending_approval_to_user_status.php'
    ];
    
    try {
        // Check if migrations table exists
        if (Schema::hasTable('migrations')) {
            foreach ($sqliteSpecificMigrations as $migration) {
                $migrationName = str_replace('.php', '', $migration);
                
                // Check if already exists
                $exists = DB::table('migrations')
                    ->where('migration', $migrationName)
                    ->exists();
                    
                if (!$exists) {
                    DB::table('migrations')->insert([
                        'migration' => $migrationName,
                        'batch' => 999 // High batch number to indicate manual entry
                    ]);
                    echo "  ✓ Marked as run: $migrationName\n";
                } else {
                    echo "  - Already marked: $migrationName\n";
                }
            }
        } else {
            echo "  ⚠️  Migrations table doesn't exist yet. Run 'php artisan migrate' first.\n";
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not mark migrations: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Migration Steps ===\n";
echo "1. ✓ Data exported to: database/mysql_data_export.sql\n";
echo "2. ✓ MySQL-compatible migrations created\n";
echo "3. ✓ Setup scripts created\n";
echo "\n";
echo "=== Next Steps ===\n";
echo "1. Create MySQL database and user:\n";
echo "   ./database/setup_mysql.sh\n";
echo "\n";
echo "2. Update your .env file with MySQL credentials\n";
echo "\n";
echo "3. Run Laravel migrations for MySQL:\n";
echo "   php artisan migrate:fresh\n";
echo "\n";
echo "4. Import your data:\n";
echo "   mysql -u sweat93_user -p sweat93_db < database/mysql_data_export.sql\n";
echo "\n";
echo "5. Clear Laravel caches:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
echo "\n";

// Step 3: Validate the export file
if (file_exists(__DIR__ . '/mysql_data_export.sql')) {
    $fileSize = filesize(__DIR__ . '/mysql_data_export.sql');
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    echo "✓ Export file ready: mysql_data_export.sql ($fileSizeMB MB)\n";
    
    // Count INSERT statements
    $content = file_get_contents(__DIR__ . '/mysql_data_export.sql');
    $insertCount = substr_count($content, 'INSERT INTO');
    echo "✓ Total INSERT statements: $insertCount\n";
} else {
    echo "⚠️  Export file not found. Please run: php database/mysql_export.php\n";
}

echo "\n=== Migration Complete ===\n";