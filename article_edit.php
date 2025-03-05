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

$article = [
    'id' => '',
    'article_number' => '',
    'description' => '',
    'manufacturer_id' => '',
    'supplier' => '',
    'purchase_price' => 0.00,
    'sales_price' => 0.00,
    'unit' => '',
    'vat_rate' => 19.00,
    'stock' => 0.00,
    'min_stock' => 0.00,
    'notes' => '',
    'image' => '',
    'active' => 1
];

$errors = [];
$success_message = '';
$is_new = true;

// Hersteller abrufen
$stmt = $pdo->query("SELECT id, name FROM manufacturers ORDER BY name");
$manufacturers = $stmt->fetchAll();

// Artikel laden, wenn eine ID übergeben wurde
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loaded_article = $stmt->fetch();
    
    if ($loaded_article) {
        $article = $loaded_article;
        $is_new = false;
    } else {
        $errors[] = 'Der angegebene Artikel wurde nicht gefunden.';
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular übernehmen
        $article['description'] = trim($_POST['description'] ?? '');
        $article['manufacturer_id'] = intval($_POST['manufacturer_id'] ?? 0);
        $article['supplier'] = trim($_POST['supplier'] ?? '');
        $article['purchase_price'] = floatval(str_replace(',', '.', $_POST['purchase_price'] ?? 0));
        $article['sales_price'] = floatval(str_replace(',', '.', $_POST['sales_price'] ?? 0));
        $article['unit'] = trim($_POST['unit'] ?? '');
        $article['vat_rate'] = floatval(str_replace(',', '.', $_POST['vat_rate'] ?? 19));
        $article['stock'] = floatval(str_replace(',', '.', $_POST['stock'] ?? 0));
        $article['min_stock'] = floatval(str_replace(',', '.', $_POST['min_stock'] ?? 0));
        $article['notes'] = trim($_POST['notes'] ?? '');
        $article['active'] = isset($_POST['active']) ? 1 : 0;

        // Bild hochladen
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            $imagePath = 'uploads/' . basename($image['name']);
            if (move_uploaded_file($image['tmp_name'], $imagePath)) {
                $article['image'] = $imagePath;
            } else {
                $errors[] = 'Das Bild konnte nicht hochgeladen werden.';
            }
        }

        // Validierung
        if (empty($article['description'])) {
            $errors[] = 'Bitte geben Sie eine Artikelbeschreibung ein.';
        }

        if ($article['manufacturer_id'] <= 0) {
            $errors[] = 'Bitte wählen Sie einen Hersteller aus.';
        }

        // Wenn keine Fehler aufgetreten sind
        if (empty($errors)) {
            if ($is_new) {
                // Artikelnummer generieren (Format: A + 6-stellige fortlaufende Nummer)
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(article_number, 2) AS UNSIGNED)) as max_num FROM articles WHERE article_number LIKE 'A%'");
                $result = $stmt->fetch();
                $nextNum = str_pad(($result['max_num'] ?? 0) + 1, 6, '0', STR_PAD_LEFT);
                $article_number = "A{$nextNum}";

                // Neuen Artikel erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO articles 
                    (article_number, description, manufacturer_id, supplier, purchase_price, sales_price, unit, vat_rate, stock, min_stock, notes, image, active, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $article_number,
                    $article['description'],
                    $article['manufacturer_id'],
                    $article['supplier'],
                    $article['purchase_price'],
                    $article['sales_price'],
                    $article['unit'],
                    $article['vat_rate'],
                    $article['stock'],
                    $article['min_stock'],
                    $article['notes'],
                    $article['image'],
                    $article['active'],
                    $_SESSION['user_id']
                ]);
                
                $success_message = 'Der Artikel wurde erfolgreich angelegt.';
            } else {
                // Bestehenden Artikel aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE articles 
                    SET 
                        description = ?,
                        manufacturer_id = ?,
                        supplier = ?,
                        purchase_price = ?,
                        sales_price = ?,
                        unit = ?,
                        vat_rate = ?,
                        stock = ?,
                        min_stock = ?,
                        notes = ?,
                        image = ?,
                        active = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $article['description'],
                    $article['manufacturer_id'],
                    $article['supplier'],
                    $article['purchase_price'],
                    $article['sales_price'],
                    $article['unit'],
                    $article['vat_rate'],
                    $article['stock'],
                    $article['min_stock'],
                    $article['notes'],
                    $article['image'],
                    $article['active'],
                    $_SESSION['user_id'],
                    $article['id']
                ]);
                
                $success_message = 'Der Artikel wurde erfolgreich aktualisiert.';
            }
            
            // Nach erfolgreichem Speichern Weiterleitung zur Artikelübersicht
            $_SESSION['success_message'] = $success_message;
            header("Location: articles.php");
            exit;
            
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
                    <i class="bi bi-box"></i>
                    <?php echo $is_new ? 'Neuen Artikel anlegen' : 'Artikel bearbeiten'; ?>
                </h1>
                <a href="articles.php" class="btn btn-secondary">
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
                    <h5 class="card-title mb-0">Artikeldaten</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($article['id']); ?>">
                        
                        <div class="mb-3">
                            <label for="article_number" class="form-label">Artikelnummer</label>
                            <input type="text" class="form-control" id="article_number" name="article_number"
                                   value="<?php echo htmlspecialchars($article['article_number']); ?>" readonly>
                            <?php if ($is_new): ?>
                                <small class="text-muted">Wird automatisch vergeben</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung *</label>
                            <input type="text" class="form-control" id="description" name="description"
                                   value="<?php echo htmlspecialchars($article['description']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="manufacturer_id" class="form-label">Hersteller *</label>
                            <select class="form-select" id="manufacturer_id" name="manufacturer_id" required>
                                <option value="">- Bitte wählen -</option>
                                <?php foreach ($manufacturers as $manufacturer): ?>
                                    <option value="<?php echo $manufacturer['id']; ?>" <?php echo $article['manufacturer_id'] == $manufacturer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($manufacturer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="supplier" class="form-label">Lieferant</label>
                            <input type="text" class="form-control" id="supplier" name="supplier"
                                   value="<?php echo htmlspecialchars($article['supplier']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="purchase_price" class="form-label">Einkaufspreis</label>
                            <input type="text" class="form-control" id="purchase_price" name="purchase_price"
                                   value="<?php echo number_format($article['purchase_price'], 2, ',', '.'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="sales_price" class="form-label">Verkaufspreis</label>
                            <input type="text" class="form-control" id="sales_price" name="sales_price"
                                   value="<?php echo number_format($article['sales_price'], 2, ',', '.'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="unit" class="form-label">Einheit</label>
                            <input type="text" class="form-control" id="unit" name="unit"
                                   value="<?php echo htmlspecialchars($article['unit']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="vat_rate" class="form-label">Mehrwertsteuersatz (%)</label>
                            <input type="text" class="form-control" id="vat_rate" name="vat_rate"
                                   value="<?php echo number_format($article['vat_rate'], 2, ',', '.'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock" class="form-label">Lagerbestand</label>
                            <input type="text" class="form-control" id="stock" name="stock"
                                   value="<?php echo number_format($article['stock'], 2, ',', '.'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="min_stock" class="form-label">Mindestbestand</label>
                            <input type="text" class="form-control" id="min_stock" name="min_stock"
                                   value="<?php echo number_format($article['min_stock'], 2, ',', '.'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Anmerkungen</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($article['notes']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Bild</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <?php if (!empty($article['image'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Artikelbild" class="img-fluid mt-2" style="max-height: 200px;">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                   <?php echo $article['active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">
                                Artikel ist aktiv
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="articles.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Artikel speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
