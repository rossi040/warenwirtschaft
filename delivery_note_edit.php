<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Debug-Ausgabe aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

$delivery_note = [
    'id' => '',
    'delivery_note_number' => '',
    'customer_id' => '',
    'order_number' => '',
    'delivery_date' => date('Y-m-d'),
    'shipping_address' => '',
    'status' => 'open',
    'notes' => ''
];

$errors = [];
$success_message = '';
$is_new = true;

// Kunden laden
try {
    $stmt = $pdo->query("SELECT id, company_name, first_name, last_name FROM customers ORDER BY company_name, last_name, first_name");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Fehler beim Laden der Kunden: " . $e->getMessage());
}

// Artikel laden
try {
    $stmt = $pdo->query("SELECT id, article_number, description FROM articles ORDER BY article_number");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Fehler beim Laden der Artikel: " . $e->getMessage());
}

// Lieferschein laden, wenn eine ID übergeben wurde
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM delivery_notes WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $loaded_delivery_note = $stmt->fetch();

        if ($loaded_delivery_note) {
            $delivery_note = $loaded_delivery_note;
            $is_new = false;
        } else {
            $errors[] = 'Der angegebene Lieferschein wurde nicht gefunden.';
        }
    } catch (PDOException $e) {
        die("Fehler beim Laden des Lieferscheins: " . $e->getMessage());
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular übernehmen
        $delivery_note['customer_id'] = intval($_POST['customer_id'] ?? 0);
        $delivery_note['order_number'] = trim($_POST['order_number'] ?? '');
        $delivery_note['delivery_date'] = trim($_POST['delivery_date'] ?? date('Y-m-d'));
        $delivery_note['shipping_address'] = trim($_POST['shipping_address'] ?? '');
        $delivery_note['status'] = trim($_POST['status'] ?? 'open');
        $delivery_note['notes'] = trim($_POST['notes'] ?? '');

        // Validierung
        if ($delivery_note['customer_id'] <= 0) {
            $errors[] = 'Bitte wählen Sie einen Kunden aus.';
        }

        // Status-Wert validieren
        $valid_statuses = ['open', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($delivery_note['status'], $valid_statuses)) {
            $errors[] = 'Ungültiger Statuswert.';
        }

        // Wenn keine Fehler aufgetreten sind
        if (empty($errors)) {
            if ($is_new) {
                // Lieferscheinnummer generieren (Format: L + Jahr + 4-stellige fortlaufende Nummer)
                $year = date('Y');
                $stmt = $pdo->query("SELECT MAX(SUBSTRING(delivery_note_number, 6)) as max_num FROM delivery_notes WHERE delivery_note_number LIKE 'L{$year}%'");
                $result = $stmt->fetch();
                $nextNum = str_pad(($result['max_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                $delivery_note_number = "L{$year}{$nextNum}";

                // Neuen Lieferschein erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_notes 
                    (delivery_note_number, customer_id, order_number, delivery_date, shipping_address, status, notes, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $delivery_note_number,
                    $delivery_note['customer_id'],
                    $delivery_note['order_number'],
                    $delivery_note['delivery_date'],
                    $delivery_note['shipping_address'],
                    $delivery_note['status'],
                    $delivery_note['notes'],
                    $_SESSION['user_id']
                ]);

                // ID des neu erstellten Lieferscheins abrufen
                $delivery_note['id'] = $pdo->lastInsertId();
                $is_new = false;

                $success_message = 'Der Lieferschein wurde erfolgreich angelegt.';
            } else {
                // Bestehenden Lieferschein aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE delivery_notes 
                    SET 
                        customer_id = ?,
                        order_number = ?,
                        delivery_date = ?,
                        shipping_address = ?,
                        status = ?,
                        notes = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $delivery_note['customer_id'],
                    $delivery_note['order_number'],
                    $delivery_note['delivery_date'],
                    $delivery_note['shipping_address'],
                    $delivery_note['status'],
                    $delivery_note['notes'],
                    $_SESSION['user_id'],
                    $delivery_note['id']
                ]);
                
                $success_message = 'Der Lieferschein wurde erfolgreich aktualisiert.';
            }
            
            // Artikel zum Lieferschein hinzufügen
            if (!empty($_POST['article_id']) && !empty($_POST['quantity'])) {
                $article_id = intval($_POST['article_id']);
                $quantity = floatval($_POST['quantity']);

                if ($article_id > 0 && $quantity > 0) {
                    // Positionnummer generieren (fortlaufend für jeden Artikel im Lieferschein)
                    $stmt = $pdo->prepare("SELECT MAX(position_number) as max_position FROM delivery_note_items WHERE delivery_note_id = ?");
                    $stmt->execute([$delivery_note['id']]);
                    $result = $stmt->fetch();
                    $position_number = ($result['max_position'] ?? 0) + 1;

                    // Beschreibung des Artikels abrufen
                    $stmt = $pdo->prepare("SELECT description FROM articles WHERE id = ?");
                    $stmt->execute([$article_id]);
                    $article_description = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("
                        INSERT INTO delivery_note_items 
                        (delivery_note_id, article_id, quantity, position_number, description, created_at, created_by) 
                        VALUES (?, ?, ?, ?, ?, NOW(), ?)
                    ");
                    $stmt->execute([
                        $delivery_note['id'],
                        $article_id,
                        $quantity,
                        $position_number,
                        $article_description,
                        $_SESSION['user_id']
                    ]);

                    $success_message = 'Der Artikel wurde erfolgreich zum Lieferschein hinzugefügt.';
                } else {
                    $errors[] = 'Bitte wählen Sie einen gültigen Artikel und eine Menge größer als Null.';
                }
            }
        }
    } catch (PDOException $e) {
        // Detaillierte Fehlermeldung ausgeben
        $errors[] = 'Datenbankfehler: ' . $e->getMessage();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-truck"></i>
                    <?php echo $is_new ? 'Neuen Lieferschein anlegen' : 'Lieferschein bearbeiten'; ?>
                </h1>
                <a href="delivery_notes.php" class="btn btn-secondary">
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

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Lieferscheindaten</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($delivery_note['id']); ?>">
                        
                        <div class="mb-3">
                            <label for="delivery_note_number" class="form-label">Lieferscheinnummer</label>
                            <input type="text" class="form-control" id="delivery_note_number" name="delivery_note_number"
                                   value="<?php echo htmlspecialchars($delivery_note['delivery_note_number']); ?>" readonly>
                            <?php if ($is_new): ?>
                                <small class="text-muted">Wird automatisch vergeben</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Kunde *</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">- Bitte wählen -</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo $delivery_note['customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer['company_name'] . ' - ' . $customer['first_name'] . ' ' . $customer['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Auftragsnummer</label>
                            <input type="text" class="form-control" id="order_number" name="order_number"
                                   value="<?php echo htmlspecialchars($delivery_note['order_number']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_date" class="form-label">Lieferdatum *</label>
                            <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                                   value="<?php echo htmlspecialchars($delivery_note['delivery_date']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Lieferadresse</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"><?php echo htmlspecialchars($delivery_note['shipping_address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="open" <?php echo $delivery_note['status'] === 'open' ? 'selected' : ''; ?>>Offen</option>
                                <option value="shipped" <?php echo $delivery_note['status'] === 'shipped' ? 'selected' : ''; ?>>Versandt</option>
                                <option value="delivered" <?php echo $delivery_note['status'] === 'delivered' ? 'selected' : ''; ?>>Geliefert</option>
                                <option value="cancelled" <?php echo $delivery_note['status'] === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Anmerkungen</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($delivery_note['notes']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="article_id" class="form-label">Artikel</label>
                            <select class="form-select" id="article_id" name="article_id">
                                <option value="">- Bitte wählen -</option>
                                <?php foreach ($articles as $article): ?>
                                    <option value="<?php echo $article['id']; ?>">
                                        <?php echo htmlspecialchars($article['article_number'] . ' - ' . $article['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Menge</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="0.01">
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="delivery_notes.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Lieferschein speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$is_new): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Artikel im Lieferschein</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Artikelnummer</th>
                                    <th>Beschreibung</th>
                                    <th>Menge</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT d.*, a.article_number, a.description FROM delivery_note_items d JOIN articles a ON d.article_id = a.id WHERE d.delivery_note_id = ?");
                                    $stmt->execute([$delivery_note['id']]);
                                    $items = $stmt->fetchAll();

                                    foreach ($items as $item) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($item['article_number']) . '</td>';
                                        echo '<td>' . htmlspecialchars($item['description']) . '</td>';
                                        echo '<td>' . number_format($item['quantity'], 2, ',', '.') . '</td>';
                                        echo '<td class="text-end">';
                                        echo '<form method="post" action="delivery_note_item_delete.php" class="d-inline">';
                                        echo '<input type="hidden" name="id" value="' . $item['id'] . '">';
                                        echo '<input type="hidden" name="delivery_note_id" value="' . $delivery_note['id'] . '">';
                                        echo '<button type="submit" name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Möchten Sie diesen Artikel wirklich entfernen?\')">';
                                        echo '<i class="bi bi-trash"></i>';
                                        echo '</button>';
                                        echo '</form>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="4" class="text-center">Fehler beim Laden der Artikel: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
