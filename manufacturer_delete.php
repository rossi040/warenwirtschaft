<?php
require_once 'config.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hersteller-ID aus der URL abrufen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        // Prüfen, ob der Hersteller existiert
        $checkStmt = $pdo->prepare("SELECT id FROM manufacturers WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() > 0) {
            // Hersteller löschen
            $deleteStmt = $pdo->prepare("DELETE FROM manufacturers WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            $_SESSION['success'] = "Hersteller wurde erfolgreich gelöscht.";
        } else {
            $_SESSION['error'] = "Hersteller nicht gefunden.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Fehler beim Löschen des Herstellers: " . $e->getMessage();
    }
}

// Zurück zur Herstellerübersicht
header('Location: manufacturers.php');
exit;
?>