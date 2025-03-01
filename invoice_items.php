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
                    <a href="invoices.php" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Zurück
                    </a>
                    <button type="button" class="btn btn-success me-2" id="saveButton">
                        <i class="bi bi-save"></i> Speichern
                    </button>
                    <a href="invoice_pdf.php?id=<?php echo $invoice_id; ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <!-- Erfolgsmeldung Container -->
            <div id="successAlert" class="alert alert-success" style="display: none;">
                Die Positionen wurden erfolgreich gespeichert.
            </div>

            <!-- Fehlermeldung Container -->
            <div id="errorAlert" class="alert alert-danger" style="display: none;">
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

            <!-- Weitere Rechnungspositionen -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Weitere Rechnungspositionen</h5>
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

    // Speichern-Button Event Handler
    document.getElementById('saveButton').addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-hourglass"></i> Speichern...';

        // Positionen sammeln
        const positions = [];
        document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
            // Überprüfen ob es eine echte Datenzeile ist (nicht die "Keine Positionen" Zeile)
            if (row.cells.length >= 4) {
                const position = {
                    article_id: row.querySelector('input[name="item_id"]')?.value,
                    quantity: parseFloat(row.cells[1].textContent),
                    price_per_unit: parseFloat(row.cells[2].textContent.replace(',', '.'))
                };
                if (position.article_id) {
                    positions.push(position);
                }
            }
        });

        // AJAX Request zum Speichern
        fetch('save_items.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                invoice_id: <?php echo $invoice_id; ?>,
                positions: positions
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Erfolgsmeldung zeigen
                const successAlert = document.getElementById('successAlert');
                successAlert.style.display = 'block';
                successAlert.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.message;
                
                // Nach 3 Sekunden ausblenden
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 3000);

                // Optional: Seite neu laden um aktualisierte Daten zu zeigen
                // window.location.reload();
            } else {
                // Fehlermeldung zeigen
                const errorAlert = document.getElementById('errorAlert');
                errorAlert.style.display = 'block';
                errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + data.error;
            }
        })
        .catch(error => {
            // Fehlermeldung bei technischen Problemen
            const errorAlert = document.getElementById('errorAlert');
            errorAlert.style.display = 'block';
            errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Technischer Fehler beim Speichern';
            console.error('Error:', error);
        })
        .finally(() => {
            // Button wieder aktivieren
            button.disabled = false;
            button.innerHTML = originalText;
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
