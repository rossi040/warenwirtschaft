<?php
// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoicing_system');

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session starten
session_start();

// Datenbankverbindung
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}
?>