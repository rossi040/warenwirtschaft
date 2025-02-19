<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$article = [
    'id' => '',
    'article_number' => '',
    'description' => '',
    'detailed_description' => '',
    'manufacturer_id' => '',
    'purchase_price' => 0.00,
    'selling_price' => 0.00,
    'stock' => 0,
    'orderable' => 1
];

$errors = [];
$success = false;

// Wenn ID übergeben wurde, lade den Artikel
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loadedArticle = $stmt->fetch();
    if ($loadedArticle) {
        $article = $loadedArticle;
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Artikeldaten aus POST
    $article = [
        'id' => $_POST['id'] ?? '',
        'description' => trim($_POST['description'] ?? ''),
        'detailed_description' => trim($_POST['detailed_description'] ?? ''),
        'manufacturer_id' => $_POST['manufacturer_id'] ?? '',
        'purchase_price' => $_POST['purchase_price'] ?? 0.00,
        'selling_price' => $_POST['selling_price'] ?? 0.00,
        'stock' => $_POST['stock'] ?? 0,
        'orderable' => isset($_POST['orderable']) ? 1 : 0
    ];

    // Validierung
    if (empty($article['description'])) {
        $errors[] = 'Bitte geben Sie eine Beschreibung an.';
    }
    if ($article['purchase_price'] < 0) {
        $errors[] = 'Bitte geben Sie einen gültigen Einkaufspreis an.';
    }
    if ($article['selling_price'] < 0) {
        $errors[] = 'Bitte geben Sie einen gültigen Verkaufspreis an.';
    }
    if ($article['stock'] < 0) {
        $errors[] = 'Bitte geben Sie einen gültigen Bestand an.';
    }

    // Wenn keine Fehler, speichern
    if (empty($errors)) {
        try {
            if (empty($article['id'])) {
                // Neue Artikelnummer generieren
                $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM articles");
                $result = $stmt->fetch();
                $maxId = $result['max_id'] + 1;
                $article['article_number'] = 'ART' . str_pad($maxId, 5, '0', STR_PAD_LEFT);

                // Neuer Artikel
                $sql = "INSERT INTO articles (article_number, description, detailed_description, manufacturer_id, purchase_price, selling_price, stock, orderable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $article['article_number'],
                    $article['description'],
                    $article['detailed_description'],
                    $article['manufacturer_id'],
                    $article['purchase_price'],
                    $article['selling_price'],
                    $article['stock'],
                    $article['orderable']
                ]);
            } else {
                // Bestehenden Artikel aktualisieren
                $sql = "UPDATE articles SET description = ?, detailed_description = ?, manufacturer_id = ?, purchase_price = ?, selling_price = ?, stock = ?, orderable = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $article['description'],
                    $article['detailed_description'],
                    $article['manufacturer_id'],
                    $article['purchase_price'],
                    $article['selling_price'],
                    $article['stock'],
                    $article['orderable'],
                    $article['id']
                ]);
            }
            $success = true;
        } catch (PDOException $e) {
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
                    <i class="bi bi-box"></i>
                    <?php echo empty($article['id']) ? 'Neuer Artikel' : 'Artikel bearbeiten'; ?>
                </h1>
                <a href="articles.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Der Artikel wurde erfolgreich gespeichert.
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
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($article['id']); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="article_number" class="form-label">Artikelnummer</label>
                                <input type="text" class="form-control" id="article_number" name="article_number"
                                       value="<?php echo htmlspecialchars($article['article_number']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="description" class="form-label">Beschreibung</label>
                                <input type="text" class="form-control" id="description" name="description"
                                       value="<?php echo htmlspecialchars($article['description']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="detailed_description" class="form-label">Detaillierte Beschreibung</label>
                                <textarea class="form-control" id="detailed_description" name="detailed_description"
                                          rows="3"><?php echo htmlspecialchars($article['detailed_description']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="manufacturer_id" class="form-label">Hersteller ID</label>
                                <input type="number" class="form-control" id="manufacturer_id" name="manufacturer_id"
                                       value="<?php echo htmlspecialchars($article['manufacturer_id']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="purchase_price" class="form-label">Einkaufspreis (€)</label>
                                <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                       value="<?php echo htmlspecialchars($article['purchase_price']); ?>" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label for="selling_price" class="form-label">Verkaufspreis (€)</label>
                                <input type="number" class="form-control" id="selling_price" name="selling_price"
                                       value="<?php echo htmlspecialchars($article['selling_price']); ?>" step="0.01">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock" class="form-label">Bestand</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                       value="<?php echo htmlspecialchars($article['stock']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="orderable" class="form-label">Bestellbar</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="orderable" name="orderable"
                                           <?php echo $article['orderable'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="orderable">Ja</label>
                                </div>
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

<?php require_once 'includes/footer.php'; ?>
