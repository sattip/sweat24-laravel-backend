<?php

/**
 * Script to export SQLite data to MySQL-compatible format
 */

// Direct SQLite connection without Laravel
$dbPath = __DIR__ . '/database.sqlite';

if (!file_exists($dbPath)) {
    die("Database file not found at: $dbPath\n");
}

$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all tables
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = [];
$output[] = "-- MySQL Database Export from SQLite";
$output[] = "-- Generated at " . date('Y-m-d H:i:s');
$output[] = "SET FOREIGN_KEY_CHECKS=0;";
$output[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
$output[] = "";

foreach ($tables as $table) {
    $tableName = $table['name'];
    
    if (in_array($tableName, ['migrations', 'personal_access_tokens', 'password_reset_tokens', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'])) {
        continue; // Skip Laravel system tables
    }
    
    echo "Exporting table: $tableName\n";
    
    $output[] = "-- Table: $tableName";
    $output[] = "TRUNCATE TABLE `$tableName`;";
    
    // Get all records
    $stmt = $db->query("SELECT * FROM `$tableName`");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        foreach ($records as $record) {
            // Prepare values
            $values = [];
            foreach ($record as $key => $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } elseif (is_numeric($value) && !is_string($value)) {
                    $values[] = $value;
                } elseif ($value === 'true' || $value === 'false') {
                    $values[] = $value === 'true' ? 1 : 0;
                } else {
                    // Properly escape for MySQL
                    $value = str_replace(['\\', "'", '"', "\n", "\r", "\t"], ['\\\\', "''", '\"', '\\n', '\\r', '\\t'], $value);
                    $values[] = "'$value'";
                }
            }
            
            $columns = '`' . implode('`, `', array_keys($record)) . '`';
            $valuesStr = implode(', ', $values);
            
            $output[] = "INSERT INTO `$tableName` ($columns) VALUES ($valuesStr);";
        }
    }
    
    $output[] = "";
}

$output[] = "SET FOREIGN_KEY_CHECKS=1;";

// Write to file
file_put_contents(__DIR__ . '/mysql_data_export.sql', implode("\n", $output));

echo "\nExport completed! File saved as: database/mysql_data_export.sql\n";
echo "Total tables exported: " . count($tables) . "\n";