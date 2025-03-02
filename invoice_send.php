<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = $_GET['id'];

try {
    // Prüfen ob die Rechnung existiert und im Entwurfsstatus ist
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        $_SESSION['error_message'] = 'Rechnung nicht gefunden';
        header('Location: invoices.php');
        exit;
    }
    
    if ($invoice['status'] !== 'draft') {
        $_SESSION['error_message'] = 'Rechnung wurde bereits versendet oder bezahlt';
        header('Location: invoices.php');
        exit;
    }
    
    // Status auf "versendet" setzen und Versanddatum erfassen
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'sent', sent_date = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $invoice_id]);
    
    $_SESSION['success_message'] = 'Rechnung wurde als versendet markiert';

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Fehler: ' . $e->getMessage();
}

header('Location: invoices.php');
exit;
?>