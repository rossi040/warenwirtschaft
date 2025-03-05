<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist und Admin-Rechte hat
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    $_SESSION['error_message'] = 'Sie haben keine Berechtigung für diese Seite.';
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';

// Standardwerte für neuen Benutzer
$user = [
    'id' => '',
    'username' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'role' => 'staff',
    'active' => 1,
    'last_login' => ''
];

$is_new = true;

// Benutzer laden, wenn ID übergeben wurde
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loaded_user = $stmt->fetch();
    
    if ($loaded_user) {
        $user = $loaded_user;
        $is_new = false;
    } else {
        $error_message = 'Benutzer nicht gefunden.';
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $active = isset($_POST['active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $errors = [];
    
    // Pflichtfelder prüfen
    if (empty($username)) {
        $errors[] = 'Benutzername ist erforderlich.';
    }
    
    if (empty($email)) {
        $errors[] = 'E-Mail-Adresse ist erforderlich.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse.';
    }
    
    if (empty($first_name)) {
        $errors[] = 'Vorname ist erforderlich.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Nachname ist erforderlich.';
    }
    
    // Bei neuem Benutzer oder Passwortänderung
    if ($is_new || !empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
    }
    
    // Prüfen, ob Benutzername oder E-Mail bereits existiert
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user['id'] ?? 0]);
        if ($stmt->fetch()['count'] > 0) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id'] ?? 0]);
        if ($stmt->fetch()['count'] > 0) {
            $errors[] = 'Diese E-Mail-Adresse ist bereits registriert.';
        }
    }
    
    // Benutzer speichern, wenn keine Fehler aufgetreten sind
    if (empty($errors)) {
        try {
            if ($is_new) {
                // Neuen Benutzer anlegen
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (username, password, email, first_name, last_name, role, active, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $username, 
                    password_hash($password, PASSWORD_DEFAULT), 
                    $email,
                    $first_name,
                    $last_name,
                    $role,
                    $active,
                    $_SESSION['user_id']
                ]);
                
                $success_message = 'Benutzer wurde erfolgreich angelegt.';
            } else {
                // Bestehenden Benutzer aktualisieren
                if (!empty($password)) {
                    // Mit Passwortänderung
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, 
                            password = ?, 
                            email = ?, 
                            first_name = ?, 
                            last_name = ?, 
                            role = ?, 
                            active = ?, 
                            updated_at = NOW(), 
                            updated_by = ? 
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $username, 
                        password_hash($password, PASSWORD_DEFAULT), 
                        $email,
                        $first_name,
                        $last_name,
                        $role,
                        $active,
                        $_SESSION['user_id'],
                        $user['id']
                    ]);
                } else {
                    // Ohne Passwortänderung
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, 
                            email = ?, 
                            first_name = ?, 
                            last_name = ?, 
                            role = ?, 
                            active = ?, 
                            updated_at = NOW(), 
                            updated_by = ? 
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $username,
                        $email,
                        $first_name,
                        $last_name,
                        $role,
                        $active,
                        $_SESSION['user_id'],
                        $user['id']
                    ]);
                }
                
                $success_message = 'Benutzer wurde erfolgreich aktualisiert.';
            }
            
            // Weiterleitung zur Benutzerübersicht mit Erfolgsmeldung
            $_SESSION['success_message'] = $success_message;
            header('Location: users.php');
            exit;
            
        } catch (PDOException $e) {
            $error_message = 'Datenbankfehler: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
        
        // Formulardaten zurückgeben, damit der Benutzer sie nicht neu eingeben muss
        $user = [
            'id' => $_POST['id'] ?? '',
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role,
            'active' => $active,
            'last_login' => $user['last_login'] ?? ''
        ];
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-person"></i>
                    <?php echo $is_new ? 'Neuen Benutzer anlegen' : 'Benutzer bearbeiten'; ?>
                </h1>
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><?php echo $is_new ? 'Neuer Benutzer' : 'Benutzer bearbeiten: ' . htmlspecialchars($user['username']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Vorname *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nachname *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Benutzername *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-Mail-Adresse *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    <?php echo $is_new ? 'Passwort *' : 'Neues Passwort (leer lassen, um beizubehalten)'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $is_new ? 'required' : ''; ?>>
                                <small class="text-muted">Mindestens 8 Zeichen</small>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">
                                    <?php echo $is_new ? 'Passwort bestätigen *' : 'Neues Passwort bestätigen'; ?>
                                </label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                       <?php echo $is_new ? 'required' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rolle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Mitarbeiter</option>
                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" 
                                               <?php echo $user['active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="active">
                                            Benutzer ist aktiv
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!$is_new && $user['last_login']): ?>
                            <div class="mb-3">
                                <label class="form-label">Letzte Anmeldung</label>
                                <p class="form-control-plaintext">
                                    <?php echo date('d.m.Y H:i:s', strtotime($user['last_login'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="users.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Benutzer speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>