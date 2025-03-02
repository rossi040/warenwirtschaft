<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Filter-Parameter
$status_filter = $_GET['status'] ?? '';
$customer_filter = $_GET['customer'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// SQL für Rechnungen mit Filtern
$sql = "SELECT i.*, c.company_name, c.first_name, c.last_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $sql .= " AND i.status = ?";
    $params[] = $status_filter;
}

if (!empty($customer_filter)) {
    $sql .= " AND i.customer_id = ?";
    $params[] = $customer_filter;
}

if (!empty($date_from)) {
    $sql .= " AND i.invoice_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND i.invoice_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY i.invoice_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Kunden für Filter-Dropdown laden
$customers = $pdo->query("SELECT id, company_name, first_name, last_name FROM customers ORDER BY company_name, last_name")->fetchAll();

// Erfolgsmeldung anzeigen, falls vorhanden
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

// Fehlermeldung anzeigen, falls vorhanden
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-file-text"></i> Rechnungen</h1>
                <a href="invoice_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neue Rechnung
                </a>
            </div>

            <!-- Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Alle Status</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Entwurf</option>
                                <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Versendet</option>
                                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Bezahlt</option>
                                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Überfällig</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="customer" class="form-label">Kunde</label>
                            <select class="form-select" id="customer" name="customer">
                                <option value="">Alle Kunden</option>
                                <?php foreach ($customers as $customer): ?>
                                    <?php 
                                    $display_name = $customer['company_name'] ? 
                                        htmlspecialchars($customer['company_name']) : 
                                        htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']);
                                    $selected = ($customer['id'] == $customer_filter) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $display_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Von Datum</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Bis Datum</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Filtern
                            </button>
                            <a href="invoices.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Zurücksetzen
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Rechnungs-Nr.</th>
                                    <th>Datum</th>
                                    <th>Kunde</th>
                                    <th>Betrag (€)</th>
                                    <th>Status</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($invoice['invoice_date'])); ?></td>
                                    <td>
                                        <?php
                                        if ($invoice['company_name']) {
                                            echo htmlspecialchars($invoice['company_name']);
                                        } else {
                                            echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($invoice['total_amount'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($invoice['status']) {
                                            case 'draft':
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Entwurf';
                                                break;
                                            case 'sent':
                                                $statusClass = 'bg-primary';
                                                $statusText = 'Versendet';
                                                break;
                                            case 'paid':
                                                $statusClass = 'bg-success';
                                                $statusText = 'Bezahlt';
                                                break;
                                            case 'overdue':
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Überfällig';
                                                break;
                                            default:
                                                $statusClass = 'bg-info';
                                                $statusText = $invoice['status'];
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="invoice_pdf.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-success" title="PDF herunterladen" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <a href="invoice_items.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Positionen bearbeiten">
                                                <i class="bi bi-list-ul"></i>
                                            </a>
                                            <a href="invoice_edit.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Rechnung bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    title="Rechnung löschen"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $invoice['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Lösch-Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Rechnung löschen</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Möchten Sie die Rechnung <?php echo htmlspecialchars($invoice['invoice_number']); ?> wirklich löschen?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                        <a href="invoice_delete.php?id=<?php echo $invoice['id']; ?>" class="btn btn-danger">Löschen</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($invoices) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Keine Rechnungen gefunden</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
