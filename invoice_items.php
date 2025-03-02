<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass eine Rechnungs-ID übergeben wurde
if (!isset($_GET['id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = $_GET['id'];
$errors = [];
$success_message = '';

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_item' && isset($_POST['article_id']) && isset($_POST['quantity']) && isset($_POST['price_per_unit'])) {
            try {
                $article_id = $_POST['article_id'];
                $quantity = (float)$_POST['quantity'];
                $price_per_unit = (float)$_POST['price_per_unit'];
                $total_price = $quantity * $price_per_unit;
                
                // Position hinzufügen
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, article_id, quantity, price_per_unit, total_price) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$invoice_id, $article_id, $quantity, $price_per_unit, $total_price]);
                
                // Gesamtsummen neu berechnen
                recalculateTotals($pdo, $invoice_id);
                
                $success_message = 'Position wurde hinzugefügt.';
            } catch (PDOException $e) {
                $errors[] = 'Fehler beim Hinzufügen der Position: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'delete_item' && isset($_POST['item_id'])) {
            try {
                // Position löschen
                $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE id = ? AND invoice_id = ?");
                $stmt->execute([$_POST['item_id'], $invoice_id]);
                
                // Gesamtsummen neu berechnen
                recalculateTotals($pdo, $invoice_id);
                
                $success_message = 'Position wurde gelöscht.';
            } catch (PDOException $e) {
                $errors[] = 'Fehler beim Löschen der Position: ' . $e->getMessage();
            }
        }
    }
}

// Funktion zum Neuberechnen der Gesamtsummen
function recalculateTotals($pdo, $invoice_id) {
    try {
        // Gesamtsumme der Positionen berechnen
        $stmt = $pdo->prepare("SELECT SUM(total_price) as subtotal FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $result = $stmt->fetch();
        $subtotal = $result['subtotal'] ?? 0;
        
        // MwSt-Satz und -Betrag berechnen
        $stmt = $pdo->prepare("SELECT vat_rate FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $result = $stmt->fetch();
        $vat_rate = $result['vat_rate'] ?? 19;
        $vat_amount = $subtotal * ($vat_rate / 100);
        $total_amount = $subtotal + $vat_amount;
        
        // Rechnung aktualisieren
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET subtotal = ?, vat_amount = ?, total_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([$subtotal, $vat_amount, $total_amount, $invoice_id]);
    } catch (PDOException $e) {
        // Fehler protokollieren
        error_log("Fehler bei Neuberechnung der Rechnungssummen: " . $e->getMessage());
    }
}

// Rechnung laden
try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die('Rechnung nicht gefunden');
    }

    // Rechnungspositionen laden
    $stmt = $pdo->prepare("
        SELECT i.*, a.description as article_description 
        FROM invoice_items i 
        LEFT JOIN articles a ON i.article_id = a.id 
        WHERE i.invoice_id = ?
        ORDER BY i.id
    ");
    $stmt->execute([$invoice_id]);
    $invoice_items = $stmt->fetchAll();

    // Artikel für das Dropdown laden
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY description");
    $articles = $stmt->fetchAll();

} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-list-ul"></i>
                    Rechnungspositionen
                </h1>
                <div>
                    <a href="invoice_edit.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Rechnung bearbeiten
                    </a>
                    <a href="invoices.php" class="btn btn-secondary me-2">
                        <i class="bi bi-list"></i> Alle Rechnungen
                    </a>
                    <a href="invoice_pdf.php?id=<?php echo $invoice_id; ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
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

            <!-- Position hinzufügen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Position hinzufügen</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_item">
                        
                        <div class="col-md-6">
                            <label for="article_id" class="form-label">Artikel</label>
                            <select class="form-select" id="article_id" name="article_id" required>
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($articles as $article): ?>
                                    <option value="<?php echo $article['id']; ?>" 
                                            data-price="<?php echo $article['selling_price']; ?>">
                                        <?php echo htmlspecialchars($article['description']); ?>
                                        (€<?php echo number_format($article['selling_price'], 2, ',', '.'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="quantity" class="form-label">Menge</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="1" min="1" required>
                        </div>

                        <div class="col-md-2">
                            <label for="price_per_unit" class="form-label">Preis (€)</label>
                            <input type="number" class="form-control" id="price_per_unit" name="price_per_unit" 
                                   step="0.01" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> Hinzufügen
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rechnungspositionen -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rechnungspositionen</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Artikel</th>
                                    <th class="text-end">Menge</th>
                                    <th class="text-end">Einzelpreis (€)</th>
                                    <th class="text-end">Gesamtpreis (€)</th>
                                    <th class="text-center">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($invoice_items)): ?>
                                    <?php foreach ($invoice_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['article_description']); ?></td>
                                            <td class="text-end"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end"><?php echo number_format($item['price_per_unit'], 2, ',', '.'); ?></td>
                                            <td class="text-end"><?php echo number_format($item['total_price'], 2, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_item">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Position wirklich löschen?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Keine Positionen vorhanden</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($invoice_items)): ?>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Netto:</th>
                                        <td class="text-end"><?php echo number_format($invoice['subtotal'], 2, ',', '.'); ?> €</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">MwSt (<?php echo $invoice['vat_rate']; ?>%):</th>
                                        <td class="text-end"><?php echo number_format($invoice['vat_amount'], 2, ',', '.'); ?> €</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Gesamt:</th>
                                        <td class="text-end"><strong><?php echo number_format($invoice['total_amount'], 2, ',', '.'); ?> €</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatische Preisberechnung
    const articleSelect = document.getElementById('article_id');
    const priceInput = document.getElementById('price_per_unit');
    
    articleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const price = selectedOption.getAttribute('data-price');
            priceInput.value = price;
        } else {
            priceInput.value = '';
        }
    });

    // Initial den Preis setzen
    if (articleSelect.value) {
        const selectedOption = articleSelect.options[articleSelect.selectedIndex];
        priceInput.value = selectedOption.getAttribute('data-price');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
