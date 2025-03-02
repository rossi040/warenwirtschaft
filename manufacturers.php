<?php
require_once 'config.php';
require_once 'header.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Hersteller aus der Datenbank abrufen
$stmt = $pdo->query("SELECT * FROM manufacturers ORDER BY company_name ASC");
$manufacturers = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Hersteller</h2>
        <a href="manufacturer_edit.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Neuer Hersteller
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
                            <th>ID</th>
                            <th>Firma</th>
                            <th>Ansprechpartner</th>
                            <th>Telefon</th>
                            <th>E-Mail</th>
                            <th>Website</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($manufacturers)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Keine Hersteller gefunden</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($manufacturers as $manufacturer): ?>
                            <tr>
                                <td><?= htmlspecialchars($manufacturer['id']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['company_name']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['contact_person']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['phone']) ?></td>
                                <td>
                                    <?php if (!empty($manufacturer['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($manufacturer['email']) ?>">
                                            <?= htmlspecialchars($manufacturer['email']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($manufacturer['website'])): ?>
                                        <a href="<?= htmlspecialchars($manufacturer['website']) ?>" target="_blank">
                                            <?= htmlspecialchars($manufacturer['website']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manufacturer_edit.php?id=<?= $manufacturer['id'] ?>" 
                                       class="btn btn-sm btn-primary">Bearbeiten</a>
                                    <a href="manufacturer_delete.php?id=<?= $manufacturer['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Sind Sie sicher, dass Sie diesen Hersteller löschen möchten?')">
                                        Löschen</a>
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

<!-- Bootstrap Icons einbinden -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- Optional: Bootstrap und JavaScript für Funktionalität -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once 'footer.php'; ?>
