<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist und Admin-Rechte hat
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    $_SESSION['error_message'] = 'Sie haben keine Berechtigung für diese Seite.';
    header('Location: index.php');
    exit;
}

// Benutzer löschen
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Verhindern, dass ein Admin sich selbst löscht
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'Sie können Ihren eigenen Benutzer nicht löschen.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = 'Benutzer wurde erfolgreich gelöscht.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Fehler beim Löschen des Benutzers: ' . $e->getMessage();
        }
    }
    
    header('Location: users.php');
    exit;
}

// Benutzer aktivieren/deaktivieren
if (isset($_POST['toggle_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = ($_POST['current_status'] == 1) ? 0 : 1;
    
    // Verhindern, dass ein Admin sich selbst deaktiviert
    if ($user_id == $_SESSION['user_id'] && $new_status == 0) {
        $_SESSION['error_message'] = 'Sie können Ihren eigenen Benutzer nicht deaktivieren.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $_SESSION['success_message'] = 'Benutzerstatus wurde erfolgreich aktualisiert.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Fehler beim Aktualisieren des Benutzerstatus: ' . $e->getMessage();
        }
    }
    
    header('Location: users.php');
    exit;
}

// Alle Benutzer laden
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM invoices WHERE created_by = u.id) as invoices_count,
           (SELECT MAX(created_at) FROM invoices WHERE created_by = u.id) as last_activity
    FROM users u
    ORDER BY u.last_name, u.first_name
");
$users = $stmt->fetchAll();

// Success/Error-Nachrichten aus Session auslesen
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Nachrichten aus Session löschen
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-people"></i> Benutzerverwaltung</h1>
                <a href="user_edit.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Neuer Benutzer
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
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Benutzerübersicht</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Benutzername</th>
                                    <th>E-Mail</th>
                                    <th>Rolle</th>
                                    <th>Status</th>
                                    <th>Letzte Anmeldung</th>
                                    <th>Aktivität</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php
                                            switch ($user['role']) {
                                                case 'admin':
                                                    echo '<span class="badge bg-danger">Administrator</span>';
                                                    break;
                                                case 'manager':
                                                    echo '<span class="badge bg-warning">Manager</span>';
                                                    break;
                                                case 'staff':
                                                    echo '<span class="badge bg-info">Mitarbeiter</span>';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($user['role']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($user['active']): ?>
                                                <span class="badge bg-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inaktiv</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['invoices_count'] > 0): ?>
                                                <?php echo $user['invoices_count']; ?> Rechnungen<br>
                                                <small>Letzte: <?php echo date('d.m.Y', strtotime($user['last_activity'])); ?></small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie den Status ändern möchten?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $user['active']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-<?php echo $user['active'] ? 'warning' : 'success'; ?>">
                                                        <i class="bi bi-<?php echo $user['active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
</div>

<?php require_once 'includes/footer.php'; ?>