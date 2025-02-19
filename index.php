<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Statistiken abrufen
try {
    $stats = [
        'customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
        'invoices' => $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
        'articles' => $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT SUM(total_amount) FROM invoices")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = [
        'customers' => 0,
        'invoices' => 0,
        'articles' => 0,
        'total_revenue' => 0
    ];
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
                <div>
                    <a href="invoices.php?action=new" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Neue Rechnung
                    </a>
                    <a href="customers.php?action=new" class="btn btn-success ms-2">
                        <i class="bi bi-person-plus"></i> Neuer Kunde
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-people"></i> Kunden
                    </h5>
                    <p class="card-text display-6"><?php echo number_format($stats['customers']); ?></p>
                    <a href="customers.php" class="btn btn-outline-light">Verwalten</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-text"></i> Rechnungen
                    </h5>
                    <p class="card-text display-6"><?php echo number_format($stats['invoices']); ?></p>
                    <a href="invoices.php" class="btn btn-outline-light">Verwalten</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-box"></i> Artikel
                    </h5>
                    <p class="card-text display-6"><?php echo number_format($stats['articles']); ?></p>
                    <a href="articles.php" class="btn btn-outline-light">Verwalten</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-currency-euro"></i> Gesamtumsatz
                    </h5>
                    <p class="card-text display-6"><?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?> €</p>
                    <a href="reports.php" class="btn btn-outline-dark">Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Letzte Aktivitäten
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Neue Rechnung erstellt</h6>
                                <small class="text-muted">Vor 3 Tagen</small>
                            </div>
                            <p class="mb-1">Rechnung #1234 für Kunde XYZ</p>
                        </a>
                        <!-- Weitere Aktivitäten hier -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-exclamation-circle"></i> Offene Aufgaben
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Unbezahlte Rechnungen</h6>
                                <small class="text-muted">2 Rechnungen</small>
                            </div>
                            <p class="mb-1">Gesamtbetrag: 1.234,56 €</p>
                        </a>
                        <!-- Weitere Aufgaben hier -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
