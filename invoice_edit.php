<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialisierung der Variablen
$invoice = [
    'id' => '',
    'invoice_number' => '',
    'customer_id' => '',
    'invoice_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'subtotal' => 0.00,
    'vat_rate' => 19.00,
    'vat_amount' => 0.00,
    'total_amount' => 0.00,
    'status' => 'draft',
    'notes' => ''
];

$invoice_items = [];
$errors = [];
$success_message = '';
$is_new = true;

// Alle Artikel für das Dropdown-Menü laden
$stmt = $pdo->query("SELECT * FROM articles ORDER BY description ASC");
$articles = $stmt->fetchAll();

// Alle Kunden für das Dropdown-Menü laden
$stmt = $pdo->query("SELECT * FROM customers ORDER BY company_name, last_name, first_name");
$customers = $stmt->fetchAll();

// Wenn eine ID übergeben wurde, lade die Rechnung
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loadedInvoice = $stmt->fetch();
    
    if ($loadedInvoice) {
        $invoice = $loadedInvoice;
        $is_new = false;
        
        // Lade die Rechnungspositionen
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$_GET['id']]);
        $invoice_items = $stmt->fetchAll();
    } else {
        $errors[] = 'Die angegebene Rechnung wurde nicht gefunden.';
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Daten aus dem Formular übernehmen
    $invoice['customer_id'] = $_POST['customer_id'] ?? '';
    $invoice['invoice_date'] = $_POST['invoice_date'] ?? '';
    $invoice['due_date'] = $_POST['due_date'] ?? '';
    $invoice['vat_rate'] = $_POST['vat_rate'] ?? 19.00;
    $invoice['notes'] = $_POST['notes'] ?? '';
    $invoice['status'] = $_POST['status'] ?? 'draft';

    // Validierung
    if (empty($invoice['customer_id'])) {
        $errors[] = 'Bitte wählen Sie einen Kunden aus.';
    }
    if (empty($invoice['invoice_date'])) {
        $errors[] = 'Bitte geben Sie ein Rechnungsdatum an.';
    }
    if (empty($invoice['due_date'])) {
        $errors[] = 'Bitte geben Sie ein Fälligkeitsdatum an.';
    }

    // Wenn keine Fehler aufgetreten sind
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($is_new) {
                // Neue Rechnungsnummer generieren (Format: RE + Jahr + Monat + 4-stellige laufende Nummer)
                $year = date('Y');
                $month = date('m');
                $stmt = $pdo->query("SELECT MAX(SUBSTRING(invoice_number, 9)) as max_num 
                                   FROM invoices 
                                   WHERE invoice_number LIKE 'RE{$year}{$month}%'");
                $result = $stmt->fetch();
                $nextNum = str_pad(($result['max_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                $invoice_number = "RE{$year}{$month}{$nextNum}";

                // Neue Rechnung erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO invoices 
                    (invoice_number, customer_id, invoice_date, due_date, subtotal, vat_rate, vat_amount, total_amount, status, notes, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([
                    $invoice_number,
                    $invoice['customer_id'],
                    $invoice['invoice_date'],
                    $invoice['due_date'],
                    0.00, // subtotal wird später berechnet
                    $invoice['vat_rate'],
                    0.00, // vat_amount wird später berechnet
                    0.00, // total_amount wird später berechnet
                    $invoice['status'],
                    $invoice['notes'],
                    $_SESSION['user_id']
                ]);

                $invoice_id = $pdo->lastInsertId();
                $success_message = 'Die Rechnung wurde erfolgreich erstellt.';
            } else {
                // Bestehende Rechnung aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE invoices 
                    SET customer_id = ?, 
                        invoice_date = ?, 
                        due_date = ?,
                        vat_rate = ?,
                        status = ?,
                        notes = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $invoice['customer_id'],
                    $invoice['invoice_date'],
                    $invoice['due_date'],
                    $invoice['vat_rate'],
                    $invoice['status'],
                    $invoice['notes'],
                    $_SESSION['user_id'],
                    $invoice['id']
                ]);
                $invoice_id = $invoice['id'];
                $success_message = 'Die Rechnung wurde erfolgreich aktualisiert.';
            }

            $pdo->commit();
            
            // Erfolgsmeldung in der Session speichern
            $_SESSION['success_message'] = $success_message;
            
            // Weiterleitung zur Bearbeitung der Rechnungspositionen
            header("Location: invoice_items.php?id=" . $invoice_id);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}

// Automatische Berechnung bei bestehenden Rechnungspositionen
if (!empty($invoice_items)) {
    $subtotal = 0;
    foreach ($invoice_items as $item) {
        $subtotal += $item['quantity'] * $item['price'];
    }
    $vat_amount = $subtotal * ($invoice['vat_rate'] / 100);
    $total_amount = $subtotal + $vat_amount;
    
    // Update der Rechnung mit den berechneten Beträgen
    if (!$is_new) {
        try {
            $stmt = $pdo->prepare("
                UPDATE invoices 
                SET subtotal = ?, vat_amount = ?, total_amount = ? 
                WHERE id = ?
            ");
            $stmt->execute([$subtotal, $vat_amount, $total_amount, $invoice['id']]);
            
            // Aktualisierte Werte anzeigen
            $invoice['subtotal'] = $subtotal;
            $invoice['vat_amount'] = $vat_amount;
            $invoice['total_amount'] = $total_amount;
        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Betragsberechnung: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-file-text"></i>
                    <?php echo $is_new ? 'Neue Rechnung erstellen' : 'Rechnung bearbeiten'; ?>
                </h1>
                <a href="invoices.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Rechnungsdaten</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="invoiceForm">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="invoice_number" class="form-label">Rechnungsnummer</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                       value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" readonly>
                                <small class="text-muted">Wird automatisch generiert</small>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Kunde *</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <?php 
                                        $display_name = $customer['company_name'] ? 
                                            htmlspecialchars($customer['company_name']) : 
                                            htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']);
                                        $selected = ($customer['id'] == $invoice['customer_id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo htmlspecialchars($customer['id']); ?>" <?php echo $selected; ?>>
                                            <?php echo $display_name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="invoice_date" class="form-label">Rechnungsdatum *</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                       value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Fälligkeitsdatum *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date"
                                       value="<?php echo htmlspecialchars($invoice['due_date']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vat_rate" class="form-label">Mehrwertsteuersatz (%)</label>
                                <input type="number" class="form-control" id="vat_rate" name="vat_rate"
                                       value="<?php echo htmlspecialchars($invoice['vat_rate']); ?>" step="0.01" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $invoice['status'] === 'draft' ? 'selected' : ''; ?>>Entwurf</option>
                                    <option value="sent" <?php echo $invoice['status'] === 'sent' ? 'selected' : ''; ?>>Versendet</option>
                                    <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Bezahlt</option>
                                    <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Überfällig</option>
                                    <option value="cancelled" <?php echo $invoice['status'] === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">Anmerkungen</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                            </div>
                        </div>

                        <?php if (!$is_new): ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Nettobetrag</h6>
                                        <p class="card-text h5"><?php echo number_format($invoice['subtotal'], 2, ',', '.'); ?> €</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">MwSt. (<?php echo number_format($invoice['vat_rate'], 1); ?>%)</h6>
                                        <p class="card-text h5"><?php echo number_format($invoice['vat_amount'], 2, ',', '.'); ?> €</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Gesamtbetrag</h6>
                                        <p class="card-text h4"><?php echo number_format($invoice['total_amount'], 2, ',', '.'); ?> €</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="invoices.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <div>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-save"></i> Rechnung speichern
                                </button>
                                <?php if (!$is_new): ?>
                                <a href="invoice_items.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-lg ms-2">
                                    <i class="bi bi-list-ul"></i> Rechnungspositionen bearbeiten
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$is_new): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Rechnungspositionen</h5>
                    <a href="invoice_items.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Positionen bearbeiten
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Pos.</th>
                                    <th>Artikel</th>
                                    <th>Beschreibung</th>
                                    <th class="text-end">Menge</th>
                                    <th class="text-end">Preis (€)</th>
                                    <th class="text-end">Summe (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoice_items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Keine Rechnungspositionen vorhanden.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($invoice_items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($item['article_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td class="text-end"><?php echo number_format($item['quantity'], 2, ',', '.'); ?></td>
                                        <td class="text-end"><?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                                        <td class="text-end"><?php echo number_format($item['quantity'] * $item['price'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($invoice_items)): ?>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Nettobetrag:</th>
                                    <th class="text-end"><?php echo number_format($invoice['subtotal'], 2, ',', '.'); ?> €</th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">MwSt. (<?php echo number_format($invoice['vat_rate'], 1); ?>%):</th>
                                    <th class="text-end"><?php echo number_format($invoice['vat_amount'], 2, ',', '.'); ?> €</th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Gesamtbetrag:</th>
                                    <th class="text-end"><?php echo number_format($invoice['total_amount'], 2, ',', '.'); ?> €</th>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
