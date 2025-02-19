<?php
require_once 'config.php';

// Nur ausführbar, wenn noch kein Benutzer existiert
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($userCount > 0) {
    die("Es existieren bereits Benutzer. Diese Datei kann aus Sicherheitsgründen nicht mehr ausgeführt werden.");
}

// Admin-Benutzer erstellen
$username = 'admin';
$password = 'admin123'; // Bitte nach erstem Login ändern!
$email = 'admin@example.com';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$username, $hashedPassword, $email]);
    
    echo "Admin-Benutzer wurde erfolgreich erstellt!<br>";
    echo "Benutzername: " . htmlspecialchars($username) . "<br>";
    echo "Passwort: " . htmlspecialchars($password) . "<br>";
    echo "Bitte ändern Sie das Passwort nach dem ersten Login!";
    
} catch (PDOException $e) {
    die("Fehler beim Erstellen des Admin-Benutzers: " . $e->getMessage());
}