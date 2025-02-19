<?php
session_start();

// Fehlermeldungen für Entwicklung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbankverbindung
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoicing_system');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// Globale Variablen
$currentDateTime = '2025-02-18 20:47:53';  // Exakte Zeit wie angegeben
$currentUser = 'admin';                 // Exakter Benutzer wie angegeben
?>
