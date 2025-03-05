<?php
$base_path = ''; // Basis-Pfad aktualisiert
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/header.php';

// Aktuelle System-Werte
$current_utc = '2025-03-05 15:24:38';
$current_user = 'rossi040';

// SQL für monatlichen Gewinn
$sql = "SELECT 
            DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
            SUM(i.total_amount) as revenue,
            SUM(ii.quantity * a.cost_price) as costs,
            SUM(i.total_amount) - SUM(ii.quantity * a.cost_price) as profit
        FROM invoices i
        JOIN invoice_items ii ON i.id = ii.invoice_id
        JOIN articles a ON ii.article_id = a.id
        WHERE i.invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
        ORDER BY month DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $profits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-piggy-bank"></i> Gewinn pro Monat</h1>
                <div class="bg-light p-2 rounded">
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> UTC: <strong><?php echo htmlspecialchars($current_utc); ?></strong><br>
                        <i class="bi bi-person"></i> User: <strong><?php echo htmlspecialchars($current_user); ?></strong>
                    </small>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <!-- Gewinntabelle -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Monatliche Gewinn
