<?php
require_once 'includes/config.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch (PDOException $e) {
        // Fehlerbehandlung
    }
}

header('Location: articles.php');
exit();
?>
