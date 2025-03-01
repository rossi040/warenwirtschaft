<?php
require_once 'config.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Prüfen ob eine ID übergeben wurde
if (!isset($_GET['id'])) {
    header('Location: manufacturers.php');
    exit;
}

try {
    // Prüfen ob der Hersteller noch mit Artikeln verknüpft ist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE manufacturer_id = ?");
    $stmt->execute([$_GET['id']]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['error_message'] = 'Der Hersteller kann nicht gelöscht werden, da noch Artikel damit verknüpft sind.';
    } else {
        // Hersteller löschen
        $stmt = $pdo->prepare("DELETE FROM manufacturers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $_SESSION['success_message'] = 'Hersteller wurde erfolgreich gelöscht.';
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Fehler beim Löschen des Herstellers.';
}

header('Location: manufacturers.php');
exit;