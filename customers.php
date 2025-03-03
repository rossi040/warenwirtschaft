<?php
require_once 'config.php';
require_once 'header.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kunden aus der Datenbank abrufen
try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY company_name, last_name");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Fehler beim Laden der Kunden: " . $e->getMessage();
    $customers = [];
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Kunden</h2>
        <a href="customer_edit.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Neuer Kunde
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
                            <th>Firma</th>
                            <th>Vorname</th>
                            <th>Nachname</th>
                            <th>E-Mail</th>
                            <th>Telefon</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Keine Kunden gefunden</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['company_name']) ?></td>
                                <td><?= htmlspecialchars($customer['first_name']) ?></td>
                                <td><?= htmlspecialchars($customer['last_name']) ?></td>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                <td><?= htmlspecialchars($customer['phone']) ?></td>
                                <td>
                                    <a href="customer_edit.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">
                                        Bearbeiten
                                    </a>
                                    <a href="customer_delete.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Sind Sie sicher, dass Sie diesen Kunden löschen möchten?')">
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