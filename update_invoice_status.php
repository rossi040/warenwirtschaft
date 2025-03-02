<?php
require_once 'includes/config.php';

try {
    // Alle offenen Rechnungen (nicht bezahlt/storniert) finden
    $stmt = $pdo->prepare("
        SELECT id, due_date, status 
        FROM invoices 
        WHERE status IN ('draft', 'sent') 
    ");
    $stmt->execute();
    $invoices = $stmt->fetchAll();

    // Heutiges Datum für Vergleich
    $today = date('Y-m-d');

    foreach ($invoices as $invoice) {
        // Nur den Status aktualisieren, wenn die Rechnung überfällig ist
        if ($invoice['status'] == 'sent' && $invoice['due_date'] < $today) {
            // Status auf "überfällig" setzen
            $updateStmt = $pdo->prepare("UPDATE invoices SET status = 'overdue' WHERE id = ?");
            $updateStmt->execute([$invoice['id']]);
        }
    }

    echo "Rechnungsstatus erfolgreich aktualisiert.";

} catch (PDOException $e) {
    echo "Fehler bei der Aktualisierung der Rechnungsstatus: " . $e->getMessage();
}
?>