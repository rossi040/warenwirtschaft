<?php
require_once 'includes/config.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch (PDOException $e) {
        // Fehlerbehandlung
    }
}

header('Location: customers.php');
exit();
?>