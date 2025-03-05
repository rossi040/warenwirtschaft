<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Basis-URL für Links
$base_url = '/warenwirtschaft';

// SQL für Umsatzübersicht
try {
    $sql = "SELECT 
                DATE_FORMAT(invoice_date, '%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as invoice_count
            FROM invoices 
            WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $current_month = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = $e->getMessage();
}

// Bericht basierend auf GET Parameter laden
$report_type = isset($_GET['type']) ? $_GET['type'] : '';
$report_data = [];
$report_title = '';

try {
    switch($report_type) {
        case 'revenue':
            $sql = "SELECT 
                        DATE_FORMAT(invoice_date, '%Y-%m') as month,
                        SUM(total_amount) as revenue
                    FROM invoices 
                    WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                    ORDER BY month DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $report_title = 'Umsatz pro Monat';
            break;

        case 'profit':
            $sql = "SELECT 
                        DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
                        SUM(i.total_amount) as revenue,
                        SUM(ii.quantity * a.cost_price) as costs,
                        SUM(i.total_amount - (ii.quantity * a.cost_price)) as profit
                    FROM invoices i
                    JOIN invoice_items ii ON i.id = ii.invoice_id
                    JOIN articles a ON ii.article_id = a.id
                    WHERE i.invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
                    ORDER BY month DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $report_title = 'Gewinn pro Monat';
            break;

        case 'bestsellers':
            $sql = "SELECT 
                        a.name as article_name,
                        COUNT(DISTINCT i.id) as sale_count,
                        SUM(ii.quantity) as total_quantity,
                        SUM(ii.quantity * ii.price) as total_revenue
                    FROM articles a
                    JOIN invoice_items ii ON a.id = ii.article_id
                    JOIN invoices i ON ii.invoice_id = i.id
                    WHERE i.invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    GROUP BY a.id, a.name
                    ORDER BY total_quantity DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $report_title = 'Meistverkaufte Artikel';
            break;
    }
} catch(PDOException $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-graph-up"></i> 
                    Berichte
                </h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Menü -->
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-list"></i> 
                                Berichtsauswahl
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="<?php echo $base_url; ?>/reports/reports.php?type=revenue" 
                                   class="list-group-item list-group-item-action <?php echo $report_type == 'revenue' ? 'active' : ''; ?>">
                                    <i class="bi bi-graph-up"></i> Umsatz pro Monat
                                </a>
                                <a href="<?php echo $base_url; ?>/reports/reports.php?type=profit" 
                                   class="list-group-item list-group-item-action <?php echo $report_type == 'profit' ? 'active' : ''; ?>">
                                    <i class="bi bi-piggy-bank"></i> Gewinn pro Monat
                                </a>
                                <a href="<?php echo $base_url; ?>/reports/reports.php?type=bestsellers" 
                                   class="list-group-item list-group-item-action <?php echo $report_type == 'bestsellers' ? 'active' : ''; ?>">
                                    <i class="bi bi-trophy"></i> Top 10 Artikel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Zurück-Button -->
                    <a href="<?php echo $base_url; ?>/index.php" class="btn btn-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>

                <!-- Berichtsbereich -->
                <div class="col-md-9">
                    <?php if ($report_type && !empty($report_data)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($report_title); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <?php if ($report_type == 'revenue'): ?>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Monat</th>
                                                    <th class="text-end">Umsatz</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['month']); ?></td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['revenue'], 2, ',', '.'); ?> €
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                    <?php elseif ($report_type == 'profit'): ?>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Monat</th>
                                                    <th class="text-end">Umsatz</th>
                                                    <th class="text-end">Kosten</th>
                                                    <th class="text-end">Gewinn</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['month']); ?></td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['revenue'], 2, ',', '.'); ?> €
                                                        </td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['costs'], 2, ',', '.'); ?> €
                                                        </td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['profit'], 2, ',', '.'); ?> €
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                    <?php elseif ($report_type == 'bestsellers'): ?>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Rang</th>
                                                    <th>Artikel</th>
                                                    <th class="text-end">Verkäufe</th>
                                                    <th class="text-end">Menge</th>
                                                    <th class="text-end">Umsatz</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data as $index => $row): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td><?php echo htmlspecialchars($row['article_name']); ?></td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['sale_count'], 0, ',', '.'); ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['total_quantity'], 0, ',', '.'); ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php echo number_format($row['total_revenue'], 2, ',', '.'); ?> €
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Bitte wählen Sie einen Bericht aus der Liste links.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
