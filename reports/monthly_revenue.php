<?php
$base_path = ''; // Basis-Pfad aktualisiert
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/header.php';

// Aktuelle System-Werte
$current_utc = '2025-03-05 15:24:38';
$current_user = 'rossi040';

// SQL für monatlichen Umsatz
$sql = "SELECT 
            DATE_FORMAT(invoice_date, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            LAG(SUM(total_amount)) OVER (ORDER BY DATE_FORMAT(invoice_date, '%Y-%m')) as prev_revenue
        FROM invoices 
        WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
        ORDER BY month DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-graph-up"></i> Umsatz pro Monat</h1>
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
                <!-- Umsatztabelle -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Monatliche Umsatzübersicht</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Monat</th>
                                        <th class="text-end">Umsatz</th>
                                        <th class="text-end">Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revenues as $revenue): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($revenue['month']); ?></td>
                                            <td class="text-end">
                                                <?php echo number_format($revenue['revenue'], 2, ',', '.'); ?> €
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                if (isset($revenue['prev_revenue'])) {
                                                    $trend = $revenue['revenue'] - $revenue['prev_revenue'];
                                                    $icon = $trend >= 0 ? 'up' : 'down';
                                                    $color = $trend >= 0 ? 'success' : 'danger';
                                                    echo '<i class="bi bi-arrow-' . $icon . '-circle text-' . $color . '"></i>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="mt-3">
                    <a href="<?php echo $base_path; ?>/berichte.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/footer.php'; ?>
