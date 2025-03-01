<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$customer = [
    'id' => '',
    'salutation' => '',
    'company_name' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'street' => '',
    'house_number' => '',
    'zip_code' => '',
    'city' => ''
];

$errors = [];
$success = false;

// Wenn ID übergeben wurde, lade den Kunden
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loadedCustomer = $stmt->fetch();
    if ($loadedCustomer) {
        $customer = $loadedCustomer;
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = [
        'id' => $_POST['id'] ?? '',
        'salutation' => trim($_POST['salutation'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'street' => trim($_POST['street'] ?? ''),
        'house_number' => trim($_POST['house_number'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'city' => trim($_POST['city'] ?? '')
    ];

    // Validierung
    if (empty($customer['first_name']) && empty($customer['company_name'])) {
        $errors[] = 'Entweder Firma oder Vorname muss ausgefüllt sein.';
    }
    if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse.';
    }

    // Wenn keine Fehler, speichern
    if (empty($errors)) {
        try {
            if (empty($customer['id'])) {
                $sql = "INSERT INTO customers (salutation, company_name, first_name, last_name, email, phone, street, house_number, zip_code, city) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $customer['salutation'],
                    $customer['company_name'],
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['email'],
                    $customer['phone'],
                    $customer['street'],
                    $customer['house_number'],
                    $customer['zip_code'],
                    $customer['city']
                ]);
            } else {
                $sql = "UPDATE customers SET 
                        salutation = ?, company_name = ?, first_name = ?, last_name = ?, 
                        email = ?, phone = ?, street = ?, house_number = ?,
                        zip_code = ?, city = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $customer['salutation'],
                    $customer['company_name'],
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['email'],
                    $customer['phone'],
                    $customer['street'],
                    $customer['house_number'],
                    $customer['zip_code'],
                    $customer['city'],
                    $customer['id']
                ]);
            }
            $success = true;
            
            // Weiterleitung zur Übersicht nach erfolgreichem Speichern
            header('Location: customers.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-person"></i>
                    <?php echo empty($customer['id']) ? 'Neuer Kunde' : 'Kunde bearbeiten'; ?>
                </h1>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Die Daten wurden erfolgreich gespeichert.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer['id']); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="salutation" class="form-label">Anrede</label>
                                <select class="form-control" id="salutation" name="salutation">
                                    <option value="">Bitte wählen...</option>
                                    <option value="Herr" <?php echo $customer['salutation'] === 'Herr' ? 'selected' : ''; ?>>Herr</option>
                                    <option value="Frau" <?php echo $customer['salutation'] === 'Frau' ? 'selected' : ''; ?>>Frau</option>
                                    <option value="Firma" <?php echo $customer['salutation'] === 'Firma' ? 'selected' : ''; ?>>Firma</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Firma</label>
                                <input type="text" class="form-control" id="company_name" name="company_name"
                                       value="<?php echo htmlspecialchars($customer['company_name']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Vorname</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?php echo htmlspecialchars($customer['first_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nachname</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($customer['last_name']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-Mail</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($customer['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="street" class="form-label">Straße</label>
                                <input type="text" class="form-control" id="street" name="street"
                                       value="<?php echo htmlspecialchars($customer['street']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="house_number" class="form-label">Hausnummer</label>
                                <input type="text" class="form-control" id="house_number" name="house_number"
                                       value="<?php echo htmlspecialchars($customer['house_number']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">PLZ</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code"
                                       value="<?php echo htmlspecialchars($customer['zip_code']); ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="city" class="form-label">Stadt</label>
                                <input type="text" class="form-control" id="city" name="city"
                                       value="<?php echo htmlspecialchars($customer['city']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
