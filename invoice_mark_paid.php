<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = $_GET['id'];

try {
    // Prüfen ob die Rechnung existiert und noch nicht bezahlt ist
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        $_SESSION['error_message'] = 'Rechnung nicht gefunden';
        header('Location: invoices.php');
        exit;
    }
    
    if ($invoice['status'] === 'paid') {
        $_SESSION['error_message'] = 'Rechnung wurde bereits als bezahlt markiert';
        header('Location: invoices.php');
        exit;
    }
    
    // Status auf "bezahlt" setzen und Zahlungsdatum erfassen
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $invoice_id]);
    
    $_SESSION['success_message'] = 'Rechnung wurde als bezahlt markiert';

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Fehler: ' . $e->getMessage();
}

header('Location: invoices.php');
exit;
?>