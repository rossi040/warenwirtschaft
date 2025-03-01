<?php
// Datenbankverbindung
$db_host = 'localhost';
$db_name = 'invoicing_system';
$db_user = 'root';
$db_pass = '';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("Datenbankfehler: " . $e->getMessage());
}

// Hilfsfunktion für Fehlermeldungen
function showError($message) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<i class="bi bi-exclamation-triangle"></i> ';
    echo htmlspecialchars($message);
    echo '</div>';
}
?>