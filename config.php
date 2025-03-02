<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Zeitzone setzen
date_default_timezone_set('UTC');

// Datenbankverbindung
// Hier den richtigen Datenbanknamen eintragen!
$db_host = 'localhost';
$db_name = 'rechnungsverwaltung';  // Ändern von 'invoicing_system' zu 'rechnungsverwaltung' oder dem richtigen Namen
$db_user = 'root';                  // Ihr Datenbankbenutzername
$db_pass = '';                      // Ihr Datenbankpasswort

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Fehler bei der Datenbankverbindung: " . $e->getMessage());
}

// Globale Funktionen und Einstellungen
// Formatiert einen Betrag als Währung
function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

// Globale Konstanten
define('APP_NAME', 'Rechnungsverwaltung');
define('APP_VERSION', '1.0.0');

// Hilfsfunktion zum Debuggen
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?>