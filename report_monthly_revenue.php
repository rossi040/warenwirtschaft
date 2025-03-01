<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

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
    $stmt = $pdo->prepare($sql);
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
                <a href="reports.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>