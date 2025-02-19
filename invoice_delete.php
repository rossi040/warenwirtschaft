<?php
require_once 'includes/config.php';

if (isset($_GET['id'])) {
    try {
        $pdo->beginTransaction();
        
        // Erst die Rechnungspositionen löschen
        $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$_GET['id']]);
        
        // Dann die Rechnung selbst löschen
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Fehler beim Löschen der Rechnung: " . $e->getMessage());
    }
}

header('Location: invoices.php');
exit();
?>