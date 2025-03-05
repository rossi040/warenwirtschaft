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

// Artikelliste abrufen
$stmt = $pdo->query("SELECT * FROM articles ORDER BY article_number");
$articles = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-box-seam"></i> Artikelliste</h1>
                <a href="article_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neuer Artikel
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Artikelnummer</th>
                                    <th>Beschreibung</th>
                                    <th>Hersteller</th>
                                    <th>Lieferant</th>
                                    <th>Einkaufspreis</th>
                                    <th>Verkaufspreis</th>
                                    <th>Bestand</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($articles)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Keine Artikel gefunden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($articles as $article): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($article['article_number'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($article['description'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($article['manufacturer'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($article['supplier'] ?? ''); ?></td>
                                            <td><?php echo number_format($article['purchase_price'], 2, ',', '.'); ?> €</td>
                                            <td><?php echo number_format($article['sales_price'], 2, ',', '.'); ?> €</td>
                                            <td><?php echo number_format($article['stock'], 2, ',', '.'); ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="article_edit.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $article['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Lösch-Bestätigungsdialog -->
                                                <div class="modal fade" id="deleteModal<?php echo $article['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Artikel löschen</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                Möchten Sie den Artikel 
                                                                <?php echo htmlspecialchars($article['description'] ?? ''); ?>
                                                                wirklich löschen?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                                <form method="post" action="article_delete.php">
                                                                    <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                                                    <button type="submit" name="delete" class="btn btn-danger">Löschen</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
