<?php
require_once 'config.php';
require_once 'header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hersteller aus der Datenbank abrufen
$stmt = $pdo->query("SELECT * FROM manufacturers ORDER BY company_name ASC");
$manufacturers = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title float-left">Hersteller</h3>
                    <a href="manufacturer_edit.php" class="btn btn-primary float-right">
                        <i class="fas fa-plus"></i> Neuer Hersteller
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
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
                            <?php foreach ($manufacturers as $manufacturer): ?>
                            <tr>
                                <td><?= htmlspecialchars($manufacturer['id']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['company_name']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['contact_person']) ?></td>
                                <td><?= htmlspecialchars($manufacturer['phone']) ?></td>
                                <td>
                                    <?php if ($manufacturer['email']): ?>
                                        <a href="mailto:<?= htmlspecialchars($manufacturer['email']) ?>">
                                            <?= htmlspecialchars($manufacturer['email']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($manufacturer['website']): ?>
                                        <a href="<?= htmlspecialchars($manufacturer['website']) ?>" target="_blank">
                                            <?= htmlspecialchars($manufacturer['website']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="manufacturer_edit.php?id=<?= $manufacturer['id'] ?>" 
                                           class="btn btn-sm btn-info" title="Bearbeiten">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manufacturer_delete.php?id=<?= $manufacturer['id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Sind Sie sicher, dass Sie diesen Hersteller löschen möchten?')"
                                           title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
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

<?php require_once 'footer.php'; ?>