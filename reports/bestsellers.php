<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

// Aktuelle System-Werte
$current_utc = '2025-02-20 07:14:32';
$current_user = 'rossi040';

// SQL für meistverkaufte Artikel
$sql = "SELECT 
            a.id as article_id,
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

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $bestsellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-trophy"></i> Top 10 Artikel</h1>
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
                <!-- Bestseller Tabelle -->
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">Meistverkaufte Artikel (letzte 30 Tage)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                    <?php foreach ($bestsellers as $index => $article): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($article['article_name']); ?></td>
                                            <td class="text-end">
                                                <?php echo number_format($article['sale_count'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($article['total_quantity'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($article['total_revenue'], 2, ',', '.'); ?> €
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
                    <a href="../berichte.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>