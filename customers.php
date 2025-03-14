<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kunde löschen
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Prüfen, ob der Kunde mit Rechnungen/Lieferscheinen verknüpft ist
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM invoices WHERE customer_id = ?) as invoice_count,
            (SELECT COUNT(*) FROM delivery_notes WHERE customer_id = ?) as delivery_note_count
    ");
    $stmt->execute([$id, $id]);
    $counts = $stmt->fetch();
    
    if ($counts['invoice_count'] > 0 || $counts['delivery_note_count'] > 0) {
        $_SESSION['error_message'] = 'Der Kunde kann nicht gelöscht werden, da er mit Rechnungen oder Lieferscheinen verknüpft ist.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = 'Kunde wurde erfolgreich gelöscht.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Fehler beim Löschen des Kunden: ' . $e->getMessage();
        }
    }
    
    header('Location: customers.php');
    exit;
}

// Filter und Suchparameter
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 25;
$offset = ($page - 1) * $items_per_page;

// SQL-Basisabfrage
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

// Suchfilter hinzufügen
if (!empty($search)) {
    $query .= " AND (customer_number LIKE ? OR company_name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Zählen der Gesamtanzahl für Pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) AS count", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_items = $stmt->fetch()['count'];
$total_pages = ceil($total_items / $items_per_page);

// Sortierung und Paginierung hinzufügen
$query .= " ORDER BY company_name, last_name, first_name LIMIT $offset, $items_per_page";

// Kunden abrufen
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

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
                    <i class="bi bi-people"></i> Kunden
                </h1>
                <a href="customer_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neuer Kunde
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
                    <form method="GET" action="customers.php" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Suche nach Kunde..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 ms-auto">
                            <a href="customers.php" class="btn btn-outline-secondary w-100">Zurücksetzen</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kundentabelle -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Kundennummer</th>
                                    <th>Name/Firma</th>
                                    <th>Kontakt</th>
                                    <th>Ort</th>
                                    <th>Telefon</th>
                                    <th>E-Mail</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Keine Kunden gefunden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($customer['customer_number'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                if (!empty($customer['company_name'])) {
                                                    echo htmlspecialchars($customer['company_name'] ?? '');
                                                } else {
                                                    echo htmlspecialchars(($customer['salutation'] ?? '') . ' ' . ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (!empty($customer['company_name']) && !empty($customer['contact_person'])) {
                                                    echo htmlspecialchars($customer['contact_person'] ?? '');
                                                } else {
                                                    echo '&mdash;';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $location = [];
                                                if (!empty($customer['zip_code'])) $location[] = $customer['zip_code'];
                                                if (!empty($customer['city'])) $location[] = $customer['city'];
                                                echo !empty($location) ? htmlspecialchars(implode(' ', $location)) : '&mdash;';
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : '&mdash;'; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($customer['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($customer['email'] ?? ''); ?>
                                                    </a>
                                                <?php else: ?>
                                                    &mdash;
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="customer_edit.php?id=<?php echo $customer['id'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $customer['id'] ?? ''; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Lösch-Bestätigungsdialog -->
                                                <div class="modal fade" id="deleteModal<?php echo $customer['id'] ?? ''; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Kunden löschen</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                Möchten Sie den Kunden 
                                                                <?php 
                                                                if (!empty($customer['company_name'])) {
                                                                    echo htmlspecialchars($customer['company_name'] ?? '');
                                                                } else {
                                                                    echo htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                                                                }
                                                                ?>
                                                                wirklich löschen?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="id" value="<?php echo $customer['id'] ?? ''; ?>">
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
                        <nav aria-label="Kunden-Navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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
