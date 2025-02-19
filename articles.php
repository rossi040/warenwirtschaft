<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Artikel laden
$stmt = $pdo->query("SELECT * FROM articles ORDER BY id");
$articles = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-box"></i> Artikel</h1>
                <a href="article_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neuer Artikel
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Artikelnummer</th>
                                    <th>Beschreibung</th>
                                    <th>Einkaufspreis (€)</th>
                                    <th>Verkaufspreis (€)</th>
                                    <th>Bestand</th>
                                    <th>Bestellbar</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['id']); ?></td>
                                    <td><?php echo htmlspecialchars($article['article_number']); ?></td>
                                    <td><?php echo htmlspecialchars($article['description']); ?></td>
                                    <td><?php echo number_format($article['purchase_price'], 2, ',', '.'); ?></td>
                                    <td><?php echo number_format($article['selling_price'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($article['stock']); ?></td>
                                    <td><?php echo $article['orderable'] ? 'Ja' : 'Nein'; ?></td>
                                    <td>
                                        <a href="article_edit.php?id=<?php echo htmlspecialchars($article['id']); ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="article_delete.php?id=<?php echo htmlspecialchars($article['id']); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Wirklich löschen?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
