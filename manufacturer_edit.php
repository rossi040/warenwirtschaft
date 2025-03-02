<?php
require_once 'config.php';
require_once 'header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hersteller-ID aus der URL abrufen (für Bearbeitungen)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$manufacturer = [];

// Bei bestehenden Herstellern, Daten laden
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
    $stmt->execute([$id]);
    $manufacturer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manufacturer) {
        $_SESSION['error'] = "Hersteller nicht gefunden.";
        header('Location: manufacturers.php');
        exit;
    }
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $address = trim($_POST['address']);
    $notes = trim($_POST['notes']);
    
    // Validierung
    $errors = [];
    if (empty($company_name)) {
        $errors[] = "Firmenname ist erforderlich.";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Bestehenden Hersteller aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE manufacturers SET 
                    company_name = ?, 
                    contact_person = ?, 
                    phone = ?, 
                    email = ?, 
                    website = ?,
                    address = ?,
                    notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $company_name, 
                    $contact_person, 
                    $phone, 
                    $email, 
                    $website,
                    $address,
                    $notes, 
                    $id
                ]);
                $_SESSION['success'] = "Hersteller wurde erfolgreich aktualisiert.";
            } else {
                // Neuen Hersteller erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO manufacturers 
                    (company_name, contact_person, phone, email, website, address, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_name, 
                    $contact_person, 
                    $phone, 
                    $email, 
                    $website,
                    $address,
                    $notes
                ]);
                $_SESSION['success'] = "Hersteller wurde erfolgreich erstellt.";
            }
            
            header('Location: manufacturers.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Datenbankfehler: " . $e->getMessage();
        }
    }
}
?>

<!-- Änderung hier: container-fluid statt container -->
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= $id > 0 ? 'Hersteller bearbeiten' : 'Neuer Hersteller' ?></h2>
        <a href="manufacturers.php" class="btn btn-secondary">Zurück zur Übersicht</a>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Firmenname *</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" 
                           value="<?= htmlspecialchars($manufacturer['company_name'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="contact_person" class="form-label">Ansprechpartner</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" 
                           value="<?= htmlspecialchars($manufacturer['contact_person'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($manufacturer['phone'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($manufacturer['email'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" 
                           value="<?= htmlspecialchars($manufacturer['website'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Adresse</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($manufacturer['address'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Notizen</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($manufacturer['notes'] ?? '') ?></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                    <a href="manufacturers.php" class="btn btn-outline-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>