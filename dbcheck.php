<?php
require_once 'config.php';

echo "<h2>Datenbank-Diagnose</h2>";
echo "<p>PHP-Version: " . phpversion() . "</p>";

// Welche Datenbank wird tatsächlich verwendet?
$result = $pdo->query('SELECT database()');
$database_name = $result->fetchColumn();
echo "<p>Aktuell verwendete Datenbank: <strong>" . $database_name . "</strong></p>";

// Überprüfen der Tabellen
echo "<h3>Verfügbare Tabellen:</h3>";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// Überprüfen der Konfiguration
echo "<h3>Konfigurationsüberprüfung:</h3>";
echo "Konfigurierte Datenbank in config.php: <strong>" . DB_NAME . "</strong><br>";

// Prüfen auf mehrere Verbindungen
echo "<h3>Session-Check:</h3>";
echo "Session-ID: " . session_id() . "<br>";
echo "Session-Daten: <pre>";
print_r($_SESSION);
echo "</pre>";
?>