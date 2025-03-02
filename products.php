<?php
require_once 'config.php';
require_once 'header.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Artikel aus der Datenbank abrufen
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Fehler beim Laden der Artikel: " . $e->getMessage();
    $products = [];
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Artikel</h2>
        <a href="product_edit.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Neuer Artikel
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Beschreibung</th>
                            <th>Preis</th>
                            <th>Steuersatz</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Keine Artikel gefunden</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td><?= number_format($product['price'], 2, ',', '.') ?> €</td>
                                <td><?= number_format($product['tax_rate'], 2, ',', '.') ?> %</td>
                                <td>
                                    <a href="product_edit.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-primary">
                                        Bearbeiten
                                    </a>
                                    <a href="product_delete.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Sind Sie sicher, dass Sie diesen Artikel löschen möchten?')">
                                        Löschen
                                    </a>
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

<?php require_once 'footer.php'; ?>