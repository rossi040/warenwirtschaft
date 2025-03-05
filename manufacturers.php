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

// Herstellliste abrufen
$stmt = $pdo->query("SELECT * FROM manufacturers ORDER BY name");
$manufacturers = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-building"></i> Herstellliste</h1>
                <a href="manufacturer_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neuer Hersteller
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Adresse</th>
                                    <th>Kontaktperson</th>
                                    <th>Telefon</th>
                                    <th>E-Mail</th>
                                    <th>Aktiv</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($manufacturers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Keine Hersteller gefunden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($manufacturers as $manufacturer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($manufacturer['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($manufacturer['address'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($manufacturer['contact_person'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($manufacturer['phone'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($manufacturer['email'] ?? ''); ?></td>
                                            <td><?php echo !empty($manufacturer['active']) ? 'Ja' : 'Nein'; ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="manufacturer_edit.php?id=<?php echo $manufacturer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $manufacturer['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Lösch-Bestätigungsdialog -->
                                                <div class="modal fade" id="deleteModal<?php echo $manufacturer['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Hersteller löschen</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                Möchten Sie den Hersteller 
                                                                <?php echo htmlspecialchars($manufacturer['name'] ?? ''); ?>
                                                                wirklich löschen?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                                <form method="post" action="manufacturer_delete.php">
                                                                    <input type="hidden" name="id" value="<?php echo $manufacturer['id']; ?>">
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
