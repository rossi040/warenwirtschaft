<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Lieferschein löschen
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    try {
        $pdo->beginTransaction();
        
        // Zuerst die Lieferscheinpositionen löschen
        $stmt = $pdo->prepare("DELETE FROM delivery_note_items WHERE delivery_note_id = ?");
        $stmt->execute([$id]);
        
        // Dann den Lieferschein selbst löschen
        $stmt = $pdo->prepare("DELETE FROM delivery_notes WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = 'Lieferschein wurde erfolgreich gelöscht.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Fehler beim Löschen des Lieferscheins: ' . $e->getMessage();
    }
    
    header('Location: delivery_notes.php');
    exit;
}

// Filter und Suchparameter
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// SQL-Basisabfrage
$query = "
    SELECT dn.*, c.company_name, c.first_name, c.last_name 
    FROM delivery_notes dn
    LEFT JOIN customers c ON dn.customer_id = c.id
    WHERE 1=1
";

$params = [];

// Statusfilter hinzufügen
if ($status_filter !== 'all') {
    $query .= " AND dn.status = ?";
    $params[] = $status_filter;
}

// Suchfilter hinzufügen
if (!empty($search)) {
    $query .= " AND (
        dn.delivery_note_number LIKE ? 
        OR c.company_name LIKE ? 
        OR c.first_name LIKE ? 
        OR c.last_name LIKE ?
    )";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Zählen der Gesamtanzahl für Pagination
$count_query = str_replace("SELECT dn.*, c.company_name, c.first_name, c.last_name", "SELECT COUNT(*) AS count", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_items = $stmt->fetch()['count'];
$total_pages = ceil($total_items / $items_per_page);

// Sortierung und Paginierung hinzufügen
$query .= " ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT $offset, $items_per_page";

// Lieferscheine abrufen
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$delivery_notes = $stmt->fetchAll();

// Erfolgsmeldung anzeigen, falls vorhanden
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Fehlermeldung anzeigen, falls vorhanden
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-truck"></i> Lieferscheine
                </h1>
                <a href="delivery_note_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neuer Lieferschein
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Filter- und Suchbereich -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="delivery_notes.php" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Lieferschein oder Kunde suchen..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Alle Status</option>
                                <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Offen</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Abgeschlossen</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter anwenden</button>
                        </div>
                        <div class="col-md-2">
                            <a href="delivery_notes.php" class="btn btn-outline-secondary w-100">Zurücksetzen</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lieferschein-Tabelle -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Lieferschein-Nr.</th>
                                    <th>Kunde</th>
                                    <th>Lieferdatum</th>
                                    <th>Status</th>
                                    <th>Erstellt am</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($delivery_notes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Keine Lieferscheine gefunden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($delivery_notes as $note): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($note['delivery_note_number']); ?></td>
                                            <td>
                                                <?php 
                                                if ($note['company_name']) {
                                                    echo htmlspecialchars($note['company_name']);
                                                } else {
                                                    echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($note['delivery_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                switch ($note['status']) {
                                                    case 'open':
                                                        $statusClass = 'bg-primary';
                                                        $statusText = 'Offen';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Abgeschlossen';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-danger';
                                                        $statusText = 'Storniert';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = $note['status'];
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($note['created_at'])); ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="delivery_note_edit.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delivery_note_pdf.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="bi bi-file-pdf"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $note['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Lösch-Bestätigungsdialog -->
                                                <div class="modal fade" id="deleteModal<?php echo $note['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Lieferschein löschen</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                Möchten Sie den Lieferschein <?php echo htmlspecialchars($note['delivery_note_number']); ?> wirklich löschen?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
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

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Lieferschein-Navigation">
                            <ul class="pagination justify-content-center mt-3">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                            Zurück
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Zurück</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                            Weiter
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Weiter</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>