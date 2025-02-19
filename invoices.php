<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Rechnungen mit Kundeninformationen laden
$sql = "SELECT i.*, c.company_name, c.first_name, c.last_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.invoice_date DESC";
$invoices = $pdo->query($sql)->fetchAll();
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
                                            <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Anzeigen">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="invoice_edit.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="invoice_pdf.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-success" title="PDF herunterladen">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <a href="invoice_delete.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Wirklich löschen?');" title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
