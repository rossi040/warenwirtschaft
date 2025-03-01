<?php
require_once 'config.php';
require_once 'header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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
    'status' => 'draft'
];

$invoice_items = [];
$errors = [];
$success_message = '';

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
        
        // Lade die Rechnungspositionen
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$_GET['id']]);
        $invoice_items = $stmt->fetchAll();
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validierung
    if (empty($_POST['customer_id'])) {
        $errors[] = 'Bitte wählen Sie einen Kunden aus.';
    }
    if (empty($_POST['invoice_date'])) {
        $errors[] = 'Bitte geben Sie ein Rechnungsdatum an.';
    }
    if (empty($_POST['due_date'])) {
        $errors[] = 'Bitte geben Sie ein Fälligkeitsdatum an.';
    }

    // Wenn keine Fehler aufgetreten sind
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if (empty($invoice['id'])) {
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
                    (invoice_number, customer_id, invoice_date, due_date, subtotal, vat_rate, vat_amount, total_amount, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $invoice_number,
                    $_POST['customer_id'],
                    $_POST['invoice_date'],
                    $_POST['due_date'],
                    0.00, // subtotal wird später berechnet
                    $_POST['vat_rate'],
                    0.00, // vat_amount wird später berechnet
                    0.00, // total_amount wird später berechnet
                    'draft'
                ]);

                $invoice_id = $pdo->lastInsertId();
            } else {
                // Bestehende Rechnung aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE invoices 
                    SET customer_id = ?, 
                        invoice_date = ?, 
                        due_date = ?,
                        vat_rate = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['customer_id'],
                    $_POST['invoice_date'],
                    $_POST['due_date'],
                    $_POST['vat_rate'],
                    $invoice['id']
                ]);
                $invoice_id = $invoice['id'];
            }

            $pdo->commit();
            
            // Weiterleitung zur Bearbeitung der Rechnungspositionen
            header("Location: invoice_items.php?id=" . $invoice_id);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
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
                    <?php echo empty($invoice['id']) ? 'Neue Rechnung' : 'Rechnung bearbeiten'; ?>
                </h1>
                <a href="invoices.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
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

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="invoice_number" class="form-label">Rechnungsnummer</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                       value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Kunde *</label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
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
                                <input type="text" class="form-control" id="status" 
                                       value="<?php echo htmlspecialchars($invoice['status']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Weiter zu Rechnungspositionen
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
