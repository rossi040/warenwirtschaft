<?php
$base_path = '/rechnungsverwaltung';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/header.php';

// Aktuelle System-Werte
$current_utc = '2025-02-20 07:18:19';
$current_user = 'rossi040';

// Prüfen ob die Datenbank erreichbar ist
try {
    $test = $db->query("SELECT 1");
    $db_status = true;
} catch(PDOException $e) {
    $db_status = false;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <!-- System Status -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-file-earmark-bar-graph"></i> 
                    Berichte
                </h1>
                <div class="bg-light p-2 rounded">
                    <div class="text-muted">
                        <i class="bi bi-clock"></i> 
                        UTC: <strong><?php echo htmlspecialchars($current_utc); ?></strong>
                        <br>
                        <i class="bi bi-person"></i> 
                        User: <strong><?php echo htmlspecialchars($current_user); ?></strong>
                        <br>
                        <i class="bi bi-database"></i>
                        DB: <strong class="<?php echo $db_status ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $db_status ? 'Verbunden' : 'Fehler'; ?>
                        </strong>
                    </div>
                </div>
            </div>

            <?php if (!$db_status): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Datenbankfehler:</strong> Keine Verbindung zur Datenbank möglich.
                    Bitte kontaktieren Sie den Administrator.
                </div>
            <?php endif; ?>

            <!-- Berichte Grid -->
            <div class="row g-3">
                <!-- Umsatzberichte -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up-arrow"></i> 
                                Umsatzberichte
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="<?php echo $base_path; ?>/reports/monthly_revenue.php" 
                                   class="list-group-item list-group-item-action <?php echo !$db_status ? 'disabled' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-calendar-check"></i> 
                                            Umsatz pro Monat
                                        </div>
                                        <span class="badge bg-primary rounded-pill">NEU</span>
                                    </div>
                                </a>
                                <a href="<?php echo $base_path; ?>/reports/monthly_profit.php" 
                                   class="list-group-item list-group-item-action <?php echo !$db_status ? 'disabled' : ''; ?>">
                                    <i class="bi bi-piggy-bank"></i> 
                                    Gewinn pro Monat
                                </a>
                                <a href="<?php echo $base_path; ?>/reports/bestsellers.php" 
                                   class="list-group-item list-group-item-action <?php echo !$db_status ? 'disabled' : ''; ?>">
                                    <i class="bi bi-trophy"></i> 
                                    Meistverkaufte Artikel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datenbankstatus Info -->
            <?php if ($db_status): ?>
                <div class="alert alert-success mt-4" role="alert">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Status:</strong> Alle Systeme arbeiten normal. Die Berichte sind verfügbar.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/footer.php'; ?>