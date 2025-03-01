<?php
require_once 'config.php';
require_once 'header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$manufacturer = [
    'id' => '',
    'salutation' => '',
    'company_name' => '',
    'contact_person' => '',
    'email' => '',
    'website' => '',
    'phone' => ''
];

$errors = [];
$success_message = '';

// Wenn eine ID übergeben wurde, laden wir die Herstellerdaten
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $manufacturer = $stmt->fetch();

    if (!$manufacturer) {
        die('Hersteller nicht gefunden');
    }
}

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validierung
    if (empty($_POST['company_name'])) {
        $errors[] = 'Firmenname ist erforderlich';
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-Mail-Adresse ist ungültig';
    }

    if (!empty($_POST['website']) && !filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Website-URL ist ungültig';
    }

    // Wenn keine Fehler aufgetreten sind, speichern wir die Daten
    if (empty($errors)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update bestehender Hersteller
            $stmt = $pdo->prepare("
                UPDATE manufacturers 
                SET salutation = ?,
                    company_name = ?,
                    contact_person = ?,
                    email = ?,
                    website = ?,
                    phone = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['salutation'],
                $_POST['company_name'],
                $_POST['contact_person'],
                $_POST['email'],
                $_POST['website'],
                $_POST['phone'],
                $_POST['id']
            ]);
            $success_message = 'Hersteller wurde erfolgreich aktualisiert';
        } else {
            // Neuen Hersteller anlegen
            $stmt = $pdo->prepare("
                INSERT INTO manufacturers 
                (salutation, company_name, contact_person, email, website, phone)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['salutation'],
                $_POST['company_name'],
                $_POST['contact_person'],
                $_POST['email'],
                $_POST['website'],
                $_POST['phone']
            ]);
            $success_message = 'Hersteller wurde erfolgreich angelegt';
        }

        // Zurück zur Übersicht nach erfolgreichem Speichern
        header('Location: manufacturers.php');
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= $manufacturer['id'] ? 'Hersteller bearbeiten' : 'Neuer Hersteller' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($manufacturer['id']) ?>">

                        <div class="form-group">
                            <label for="salutation">Anrede</label>
                            <select class="form-control" id="salutation" name="salutation">
                                <option value="">Bitte wählen...</option>
                                <option value="Herr" <?= $manufacturer['salutation'] == 'Herr' ? 'selected' : '' ?>>Herr</option>
                                <option value="Frau" <?= $manufacturer['salutation'] == 'Frau' ? 'selected' : '' ?>>Frau</option>
                                <option value="Firma" <?= $manufacturer['salutation'] == 'Firma' ? 'selected' : '' ?>>Firma</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="company_name">Firmenname *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?= htmlspecialchars($manufacturer['company_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact_person">Ansprechpartner</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                   value="<?= htmlspecialchars($manufacturer['contact_person']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">E-Mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($manufacturer['email']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?= htmlspecialchars($manufacturer['website']) ?>"
                                   placeholder="https://">
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= htmlspecialchars($manufacturer['phone']) ?>">
                        </div>

                        <div class="form-group">
                            <a href="manufacturers.php" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-primary">Speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
