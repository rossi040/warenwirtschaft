<?php
// Verbindung zur Datenbank herstellen und Header einbinden
require_once 'config.php';
require_once 'header.php';

try {
    // Geänderte Abfrage - Sortierung nach description statt name
    $stmt = $pdo->prepare("
        SELECT a.*, m.company_name 
        FROM articles a
        LEFT JOIN manufacturers m ON a.manufacturer_id = m.id
        ORDER BY a.description ASC
    ");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Fehler beim Abrufen der Artikel: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $articles = [];
}
?>

<!-- HTML-Teil zur Anzeige der Artikel -->
<div class="container mt-4">
    <h1>Artikelverwaltung</h1>
    
    <!-- Buttons zum Hinzufügen -->
    <div class="mb-3">
        <a href="article_add.php" class="btn btn-primary">Neuen Artikel hinzufügen</a>
    </div>
    
    <!-- Artikelliste -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Artikelnummer</th>
                    <th>Beschreibung</th>
                    <th>Hersteller</th>
                    <th>Einkaufspreis</th>
                    <th>Verkaufspreis</th>
                    <th>Bestand</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td><?= htmlspecialchars($article['article_number']) ?></td>
                    <td><?= htmlspecialchars($article['description']) ?></td>
                    <td><?= htmlspecialchars($article['company_name'] ?? 'Kein Hersteller') ?></td>
                    <td><?= number_format($article['purchase_price'], 2, ',', '.') ?> €</td>
                    <td><?= number_format($article['selling_price'], 2, ',', '.') ?> €</td>
                    <td><?= htmlspecialchars($article['stock']) ?></td>
                    <td>
                        <a href="article_edit.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
                        <a href="article_delete.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sind Sie sicher, dass Sie diesen Artikel löschen möchten?')">Löschen</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (count($articles) == 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Keine Artikel gefunden</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>