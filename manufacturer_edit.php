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

$manufacturer = [
    'id' => '',
    'name' => '',
    'address' => '',
    'contact_person' => '',
    'phone' => '',
    'email' => '',
    'notes' => '',
    'active' => 1
];

$errors = [];
$success_message = '';
$is_new = true;

// Hersteller laden, wenn eine ID übergeben wurde
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loaded_manufacturer = $stmt->fetch();
    
    if ($loaded_manufacturer) {
        $manufacturer = array_merge($manufacturer, $loaded_manufacturer);
        $is_new = false;
    } else {
        $errors[] = 'Der angegebene Hersteller wurde nicht gefunden.';
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular übernehmen
        $manufacturer['name'] = trim($_POST['name'] ?? '');
        $manufacturer['address'] = trim($_POST['address'] ?? '');
        $manufacturer['contact_person'] = trim($_POST['contact_person'] ?? '');
        $manufacturer['phone'] = trim($_POST['phone'] ?? '');
        $manufacturer['email'] = trim($_POST['email'] ?? '');
        $manufacturer['notes'] = trim($_POST['notes'] ?? '');
        $manufacturer['active'] = isset($_POST['active']) ? 1 : 0;

        // Validierung
        if (empty($manufacturer['name'])) {
            $errors[] = 'Bitte geben Sie einen Namen ein.';
        }

        // Wenn keine Fehler aufgetreten sind
        if (empty($errors)) {
            if ($is_new) {
                // Neuen Hersteller erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO manufacturers (name, address, contact_person, phone, email, notes, active, created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $manufacturer['name'],
                    $manufacturer['address'],
                    $manufacturer['contact_person'],
                    $manufacturer['phone'],
                    $manufacturer['email'],
                    $manufacturer['notes'],
                    $manufacturer['active'],
                    $_SESSION['user_id']
                ]);
                
                $success_message = 'Der Hersteller wurde erfolgreich angelegt.';
            } else {
                // Bestehenden Hersteller aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE manufacturers 
                    SET 
                        name = ?,
                        address = ?,
                        contact_person = ?,
                        phone = ?,
                        email = ?,
                        notes = ?,
                        active = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $manufacturer['name'],
                    $manufacturer['address'],
                    $manufacturer['contact_person'],
                    $manufacturer['phone'],
                    $manufacturer['email'],
                    $manufacturer['notes'],
                    $manufacturer['active'],
                    $_SESSION['user_id'],
                    $manufacturer['id']
                ]);
                
                $success_message = 'Der Hersteller wurde erfolgreich aktualisiert.';
            }
            
            // Nach erfolgreichem Speichern Weiterleitung zur Herstellerübersicht
            $_SESSION['success_message'] = $success_message;
            header("Location: manufacturers.php");
            exit;
            
        }
    } catch (PDOException $e) {
        // Detaillierte Fehlermeldung ausgeben
        $errors[] = 'Datenbankfehler: ' . $e->getMessage();
    }
}

?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-building"></i>
                    <?php echo $is_new ? 'Neuen Hersteller anlegen' : 'Hersteller bearbeiten'; ?>
                </h1>
                <a href="manufacturers.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Herstellerdaten</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($manufacturer['id'] ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?php echo htmlspecialchars($manufacturer['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($manufacturer['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Kontaktperson</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                   value="<?php echo htmlspecialchars($manufacturer['contact_person'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($manufacturer['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($manufacturer['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Anmerkungen</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($manufacturer['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                   <?php echo !empty($manufacturer['active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">
                                Hersteller ist aktiv
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="manufacturers.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Hersteller speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
