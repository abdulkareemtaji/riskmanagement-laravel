<?php

/**
 * Risk Management System Setup Script
 * 
 * This script helps set up the Risk Management System with MySQL database.
 */

echo "=== Risk Management System Setup ===\n\n";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "❌ .env file not found. Please copy .env.example to .env first.\n";
    exit(1);
}

// Load environment variables
$envContent = file_get_contents('.env');
preg_match('/DB_HOST=(.*)/', $envContent, $hostMatch);
preg_match('/DB_PORT=(.*)/', $envContent, $portMatch);
preg_match('/DB_DATABASE=(.*)/', $envContent, $dbMatch);
preg_match('/DB_USERNAME=(.*)/', $envContent, $userMatch);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $passMatch);

$host = $hostMatch[1] ?? '127.0.0.1';
$port = $portMatch[1] ?? '3306';
$database = $dbMatch[1] ?? 'risk_management';
$username = $userMatch[1] ?? 'root';
$password = $passMatch[1] ?? '';

echo "Database Configuration:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . (empty($password) ? '(empty)' : '***') . "\n\n";

// Test database connection (without database name)
try {
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL connection successful\n";
} catch (PDOException $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease ensure:\n";
    echo "1. MySQL server is running\n";
    echo "2. Database credentials in .env are correct\n";
    echo "3. MySQL user has necessary privileges\n";
    exit(1);
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$database' created/verified\n";
} catch (PDOException $e) {
    echo "❌ Failed to create database: " . $e->getMessage() . "\n";
    exit(1);
}

// Test connection to the specific database
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    echo "✅ Database connection verified\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Run Laravel commands
echo "Running Laravel setup commands...\n\n";

$commands = [
    'php artisan config:clear' => 'Clearing configuration cache',
    'php artisan migrate' => 'Running database migrations',
    'php artisan db:seed' => 'Seeding database with initial data',
    'php artisan l5-swagger:generate' => 'Generating API documentation'
];

foreach ($commands as $command => $description) {
    echo "🔄 $description...\n";
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ $description completed\n";
    } else {
        echo "❌ $description failed:\n";
        echo implode("\n", $output) . "\n";
        exit(1);
    }
    echo "\n";
}

echo "=== Setup Complete! ===\n\n";
echo "🎉 Risk Management System is ready to use!\n\n";
echo "Next steps:\n";
echo "1. Start the development server: php artisan serve\n";
echo "2. Visit API documentation: http://localhost:8000/api/documentation\n";
echo "3. Test API endpoints with the provided credentials\n\n";

echo "Default Users:\n";
echo "┌─────────────┬─────────────────────────────────┬─────────────┐\n";
echo "│ Role        │ Email                           │ Password    │\n";
echo "├─────────────┼─────────────────────────────────┼─────────────┤\n";
echo "│ Admin       │ admin@riskmanagement.com        │ admin123    │\n";
echo "│ Risk Manager│ manager@riskmanagement.com      │ manager123  │\n";
echo "│ Risk Owner  │ owner@riskmanagement.com        │ owner123    │\n";
echo "│ Auditor     │ auditor@riskmanagement.com      │ auditor123  │\n";
echo "└─────────────┴─────────────────────────────────┴─────────────┘\n";