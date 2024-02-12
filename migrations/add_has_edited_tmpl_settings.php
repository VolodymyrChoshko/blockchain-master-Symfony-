<?php
/*
Usage: 
    php add_has_edited_tmpl_settings.php up
    php add_has_edited_tmpl_settings.php down
*/

require(__DIR__ . '/../vendor/autoload.php'); 

use Symfony\Component\Yaml\Yaml;

// Read the database connection details from config.yaml
$config = Yaml::parseFile(__DIR__. '/../config/config.yaml');

$dbHost = $config['parameters']['pdo']['host'];
$dbName = $config['parameters']['pdo']['name'];
$dbUser = $config['parameters']['pdo']['username'];
$dbPass = $config['parameters']['pdo']['password'];


// Establish a PDO database connection
$dsn = "mysql:host=$dbHost;dbname=$dbName";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check the command-line arguments
if (!isset($argv[1])) {
    die("Please provide an argument: 'up' or 'down'.");
}

$argument = strtolower($argv[1]);

// Define the migration SQL queries
$upQuery = "ALTER TABLE usr_users ADD usr_edited_tmpl_settings tinyint NOT NULL DEFAULT '0'";
$downQuery = "ALTER TABLE usr_users DROP COLUMN usr_edited_tmpl_settings";

// Execute the migration query
try {
    switch ($argument) {
        case 'up':
            $pdo->exec($upQuery);
            echo "Migration successful. Added the column 'has_edited_tmpl_settings' to 'usr_users' table.";
            break;
        case 'down':
            $pdo->exec($downQuery);
            echo "Migration successful. Removed the column 'has_edited_tmpl_settings' from 'usr_users' table.";
            break;
        default:
            echo "Invalid argument. Please use 'up' or 'down'.";
            break;
    }
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}



