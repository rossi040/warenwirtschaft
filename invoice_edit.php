<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Grunddaten für neue Rechnung
$invoice = [
    'id' => '',
    'invoice_number' => '',
    'invoice_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'customer_id' => '',
    'status' => 'draft',
    'notes' => '',
    'total_amount' => 0
];

$invoice_items = [];
$errors = [];
$success = false;

// Kunden für Auswahlfeld laden
$customers = $pdo->query("SELECT * FROM customers ORDER BY company_name, last_name, first_name")->fetchAll();

// Artikel für Auswahlfeld laden
$articles = $pdo->query("SELECT * FROM articles ORDER BY name")->fetchAll();

// Wenn ID übergeben wurde, lade die Rechnung
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loadedInvoice = $stmt->fetch();
    if ($loadedInvoice) {
        $invoice = $loadedInvoice;
        
        // Rechnungspositionen laden
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$_GET['id']]);
        $invoice_items = $stmt->fetchAll();
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rechnungsdaten aus POST
    $invoice = [
        'id' => $_POST['id'] ?? '',
        'invoice_number' => trim($_POST['invoice_number'] ?? ''),
        'invoice_date' => $_POST['invoice_date'] ?? date('Y-m-d'),
        'due_date' => $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
        'customer_id' => $_POST['customer_id'] ?? '',
        'status' => $_POST['status'] ?? 'draft',
        'notes' => trim($_POST['notes'] ?? ''),
        'total_amount' => 0
    ];

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

    // Wenn keine Fehler, speichern
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if (empty($invoice['id'])) {
                // Neue Rechnung
                $sql = "INSERT INTO invoices (invoice_number, invoice_date, due_date, customer_id, status, notes, total_amount) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $invoice['invoice_number'],
                    $invoice['invoice_date'],
                    $invoice['due_date'],
                    $invoice['customer_id'],
                    $invoice['status'],
                    $invoice['notes'],
                    0
                ]);
                $invoice['id'] = $pdo->lastInsertId();
            } else {
                // Bestehende Rechnung aktualisieren
                $sql = "UPDATE invoices SET 
                        invoice_number = ?, invoice_date = ?, due_date = ?,
                        customer_id = ?, status = ?, notes = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $invoice['invoice_number'],
                    $invoice['invoice_date'],
                    $invoice['due_date'],
                    $invoice['customer_id'],
                    $invoice['status'],
                    $invoice['notes'],
                    $invoice['id']
                ]);

                // Alte Rechnungspositionen löschen
                $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
                $stmt->execute([$invoice['id']]);
            }

            // Neue Rechnungspositionen speichern
            if (isset($_POST['items'])) {
                $total_amount = 0;
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['article_id'])) {
                        $sql = "INSERT INTO invoice_items (invoice_id, article_id, quantity, price, vat_rate) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $invoice['id'],
                            $item['article_id'],
                            $item['quantity'],
                            $item['price'],
                            $item['vat_rate']
                        ]);
                        $total_amount += $item['quantity'] * $item['price'];
                    }
                }

                // Gesamtbetrag aktualisieren
                $stmt = $pdo->prepare("UPDATE invoices SET total_amount = ? WHERE id = ?");
                $stmt->execute([$total_amount, $invoice['id']]);
            }

            $pdo->commit();
            $success = true;
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
                <div>
                    <a href="invoices.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Zurück
                    </a>
                    <?php if (!empty($invoice['id'])): ?>
                    <a href="invoice_pdf.php?id=<?php echo $invoice['id']; ?>" class="btn btn-success">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Die Rechnung wurde erfolgreich gespeichert.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" id="invoiceForm">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="invoice_number" class="form-label">Rechnungsnummer</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                       value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="invoice_date" class="form-label">Rechnungsdatum</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                       value="<?php echo $invoice['invoice_date']; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label">Fälligkeitsdatum</label>
                                <input type="date" class="form-control" id="due_date" name="due_date"
                                       value="<?php echo $invoice['due_date']; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $invoice['status'] == 'draft' ? 'selected' : ''; ?>>Entwurf</option>
                                    <option value="sent" <?php echo $invoice['status'] == 'sent' ? 'selected' : ''; ?>>Versendet</option>
                                    <option value="paid" <?php echo $invoice['status'] == 'paid' ? 'selected' : ''; ?>>Bezahlt</option>
                                    <option value="overdue" <?php echo $invoice['status'] == 'overdue' ? 'selected' : ''; ?>>Überfällig</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Kunde</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">-- Kunde auswählen --</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"
                                                <?php echo $invoice['customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                                            <?php
                                            echo htmlspecialchars(
                                                $customer['company_name'] 
                                                ? $customer['company_name'] 
                                                : $customer['first_name'] . ' ' . $customer['last_name']
                                            );
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <h4 class="mt-4 mb-3">Rechnungspositionen</h4>
                        <div class="table-responsive">
                            <table class="table table-striped" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Artikel</th>
                                        <th>Menge</th>
                                        <th>Preis (€)</th>
                                        <th>MwSt (%)</th>
                                        <th>Gesamt</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($invoice_items)):
                                        foreach ($invoice_items as $index => $item):
                                    ?>
                                    <tr>
                                        <td>
                                            <select name="items[<?php echo $index; ?>][article_id]" class="form-select article-select" required>
                                                <option value="">-- Artikel auswählen --</option>
                                                <?php foreach ($articles as $article): ?>
                                                    <option value="<?php echo $article['id']; ?>"
                                                            data-price="<?php echo $article['price']; ?>"
                                                            data-vat="<?php echo $article['vat_rate']; ?>"
                                                            <?php echo $item['article_id'] == $article['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($article['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?php echo $index; ?>][quantity]"
                                                   class="form-control quantity" value="<?php echo $item['quantity']; ?>"
                                                   min="1" step="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?php echo $index; ?>][price]"
                                                   class="form-control price" value="<?php echo $item['price']; ?>"
                                                   step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?php echo $index; ?>][vat_rate]"
                                                   class="form-control vat" value="<?php echo $item['vat_rate']; ?>"
                                                   required>
                                        </td>
                                        <td class="item-total">0,00 €</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-success btn-sm" id="addItem">
                                                <i class="bi bi-plus-circle"></i> Position hinzufügen
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Gesamtsumme:</strong></td>
                                        <td id="totalAmount">0,00 €</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">Bemerkungen</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsTable = document.getElementById('itemsTable');
    const addItemBtn = document.getElementById('addItem');
    let itemCount = <?php echo count($invoice_items); ?>;

    // Funktion zum Aktualisieren der Zeilensumme
    function updateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat

const price = parseFloat(row.querySelector('.price').value) || 0;
        const total = quantity * price;
        row.querySelector('.item-total').textContent = total.toFixed(2) + ' €';
        updateTotal();
    }

    // Funktion zum Aktualisieren der Gesamtsumme
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.item-total').forEach(cell => {
            total += parseFloat(cell.textContent) || 0;
        });
        document.getElementById('totalAmount').textContent = total.toFixed(2) + ' €';
    }

    // Artikel-Select onChange Handler
    function handleArticleSelect(select) {
        const option = select.options[select.selectedIndex];
        const row = select.closest('tr');
        if (option.dataset.price) {
            row.querySelector('.price').value = option.dataset.price;
        }
        if (option.dataset.vat) {
            row.querySelector('.vat').value = option.dataset.vat;
        }
        updateRowTotal(row);
    }

    // Event-Listener für Zeile hinzufügen
    function addRowEventListeners(row) {
        const select = row.querySelector('.article-select');
        const quantity = row.querySelector('.quantity');
        const price = row.querySelector('.price');
        const removeBtn = row.querySelector('.remove-item');

        select.addEventListener('change', () => handleArticleSelect(select));
        quantity.addEventListener('input', () => updateRowTotal(row));
        price.addEventListener('input', () => updateRowTotal(row));
        removeBtn.addEventListener('click', () => {
            row.remove();
            updateTotal();
        });
    }

    // Neue Position hinzufügen
    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select name="items[${itemCount}][article_id]" class="form-select article-select" required>
                    <option value="">-- Artikel auswählen --</option>
                    <?php foreach ($articles as $article): ?>
                        <option value="<?php echo $article['id']; ?>"
                                data-price="<?php echo $article['price']; ?>"
                                data-vat="<?php echo $article['vat_rate']; ?>">
                            <?php echo htmlspecialchars($article['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" name="items[${itemCount}][quantity]"
                       class="form-control quantity" value="1"
                       min="1" step="1" required>
            </td>
            <td>
                <input type="number" name="items[${itemCount}][price]"
                       class="form-control price" value="0.00"
                       step="0.01" required>
            </td>
            <td>
                <input type="number" name="items[${itemCount}][vat_rate]"
                       class="form-control vat" value="19"
                       required>
            </td>
            <td class="item-total">0,00 €</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        itemsTable.querySelector('tbody').appendChild(newRow);
        itemCount++;

        // Event-Listener für neue Zeile
        addRowEventListeners(newRow);
    });

    // Event-Listener für bestehende Zeilen
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        addRowEventListeners(row);
        updateRowTotal(row);
    });

    // Initialer Update der Gesamtsumme
    updateTotal();
});
</script>

<?php require_once 'includes/footer.php'; ?>
