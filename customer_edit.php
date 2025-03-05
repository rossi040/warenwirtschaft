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

// Initialisierung der Variablen
$customer = [
    'id' => '',
    'customer_number' => '',
    'salutation' => '',
    'company_name' => '',
    'contact_person' => '',
    'first_name' => '',
    'last_name' => '',
    'street' => '',
    'house_number' => '',
    'zip_code' => '',
    'city' => '',
    'country' => 'Deutschland',
    'phone' => '',
    'mobile' => '',
    'fax' => '',
    'email' => '',
    'website' => '',
    'tax_number' => '',
    'vat_id' => '',
    'payment_terms' => 30,
    'notes' => ''
];

$errors = [];
$success_message = '';
$is_new = true;

// Wenn eine ID übergeben wurde, lade den Kunden
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $loaded_customer = $stmt->fetch();
    
    if ($loaded_customer) {
        $customer = $loaded_customer;
        $is_new = false;
    } else {
        $errors[] = 'Der angegebene Kunde wurde nicht gefunden.';
    }
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular übernehmen
        $customer['salutation'] = $_POST['salutation'] ?? '';
        $customer['company_name'] = trim($_POST['company_name'] ?? '');
        $customer['contact_person'] = trim($_POST['contact_person'] ?? '');
        $customer['first_name'] = trim($_POST['first_name'] ?? '');
        $customer['last_name'] = trim($_POST['last_name'] ?? '');
        $customer['street'] = trim($_POST['street'] ?? '');
        $customer['house_number'] = trim($_POST['house_number'] ?? '');
        $customer['zip_code'] = trim($_POST['zip_code'] ?? '');
        $customer['city'] = trim($_POST['city'] ?? '');
        $customer['country'] = trim($_POST['country'] ?? 'Deutschland');
        $customer['phone'] = trim($_POST['phone'] ?? '');
        $customer['mobile'] = trim($_POST['mobile'] ?? '');
        $customer['fax'] = trim($_POST['fax'] ?? '');
        $customer['email'] = trim($_POST['email'] ?? '');
        $customer['website'] = trim($_POST['website'] ?? '');
        $customer['tax_number'] = trim($_POST['tax_number'] ?? '');
        $customer['vat_id'] = trim($_POST['vat_id'] ?? '');
        $customer['payment_terms'] = intval($_POST['payment_terms'] ?? 30);
        $customer['notes'] = trim($_POST['notes'] ?? '');

        // Validierung
        if (empty($customer['first_name']) && empty($customer['company_name'])) {
            $errors[] = 'Bitte geben Sie einen Namen oder eine Firma an.';
        }
        
        if (empty($customer['last_name']) && empty($customer['company_name'])) {
            $errors[] = 'Bitte geben Sie einen Nachnamen oder eine Firma an.';
        }
        
        if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Die angegebene E-Mail-Adresse ist ungültig.';
        }

        // Wenn keine Fehler aufgetreten sind
        if (empty($errors)) {
            if ($is_new) {
                // Kundennummer generieren (Format: K + Jahr + 4-stellige fortlaufende Nummer)
                $year = date('Y');
                $stmt = $pdo->query("SELECT MAX(SUBSTRING(customer_number, 6)) as max_num FROM customers WHERE customer_number LIKE 'K{$year}%'");
                $result = $stmt->fetch();
                $nextNum = str_pad(($result['max_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
                $customer_number = "K{$year}{$nextNum}";

                // Neuen Kunden erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO customers 
                    (customer_number, salutation, company_name, contact_person, first_name, last_name, 
                     street, house_number, zip_code, city, country, 
                     phone, mobile, fax, email, website, tax_number, vat_id, payment_terms, notes, 
                     created_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $customer_number,
                    $customer['salutation'],
                    $customer['company_name'],
                    $customer['contact_person'],
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['street'],
                    $customer['house_number'],
                    $customer['zip_code'],
                    $customer['city'],
                    $customer['country'],
                    $customer['phone'],
                    $customer['mobile'],
                    $customer['fax'],
                    $customer['email'],
                    $customer['website'],
                    $customer['tax_number'],
                    $customer['vat_id'],
                    $customer['payment_terms'],
                    $customer['notes'],
                    $_SESSION['user_id']
                ]);
                
                $success_message = 'Der Kunde wurde erfolgreich angelegt.';
            } else {
                // Bestehenden Kunden aktualisieren
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET 
                        salutation = ?,
                        company_name = ?,
                        contact_person = ?,
                        first_name = ?,
                        last_name = ?,
                        street = ?,
                        house_number = ?,
                        zip_code = ?,
                        city = ?,
                        country = ?,
                        phone = ?,
                        mobile = ?,
                        fax = ?,
                        email = ?,
                        website = ?,
                        tax_number = ?,
                        vat_id = ?,
                        payment_terms = ?,
                        notes = ?,
                        updated_at = NOW(),
                        updated_by = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $customer['salutation'],
                    $customer['company_name'],
                    $customer['contact_person'],
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['street'],
                    $customer['house_number'],
                    $customer['zip_code'],
                    $customer['city'],
                    $customer['country'],
                    $customer['phone'],
                    $customer['mobile'],
                    $customer['fax'],
                    $customer['email'],
                    $customer['website'],
                    $customer['tax_number'],
                    $customer['vat_id'],
                    $customer['payment_terms'],
                    $customer['notes'],
                    $_SESSION['user_id'],
                    $customer['id']
                ]);
                
                $success_message = 'Der Kunde wurde erfolgreich aktualisiert.';
            }
            
            // Nach erfolgreichem Speichern Weiterleitung zur Kundenübersicht
            $_SESSION['success_message'] = $success_message;
            header("Location: customers.php");
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
                    <i class="bi bi-person"></i>
                    <?php echo $is_new ? 'Neuen Kunden anlegen' : 'Kunden bearbeiten'; ?>
                </h1>
                <a href="customers.php" class="btn btn-secondary">
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
                    <h5 class="card-title mb-0">Kundendaten</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer['id']); ?>">
                        
                        <!-- Allgemeine Informationen -->
                        <h6 class="fw-bold mb-3">Allgemeine Informationen</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="customer_number" class="form-label">Kundennummer</label>
                                <input type="text" class="form-control" id="customer_number" name="customer_number"
                                       value="<?php echo htmlspecialchars($customer['customer_number']); ?>" readonly>
                                <?php if ($is_new): ?>
                                    <small class="text-muted">Wird automatisch vergeben</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="salutation" class="form-label">Anrede</label>
                                <select class="form-select" id="salutation" name="salutation">
                                    <option value="" <?php echo $customer['salutation'] === '' ? 'selected' : ''; ?>>- Bitte wählen -</option>
                                    <option value="Herr" <?php echo $customer['salutation'] === 'Herr' ? 'selected' : ''; ?>>Herr</option>
                                    <option value="Frau" <?php echo $customer['salutation'] === 'Frau' ? 'selected' : ''; ?>>Frau</option>
                                    <option value="Firma" <?php echo $customer['salutation'] === 'Firma' ? 'selected' : ''; ?>>Firma</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Firmenname</label>
                                <input type="text" class="form-control" id="company_name" name="company_name"
                                       value="<?php echo htmlspecialchars($customer['company_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">Ansprechpartner</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                       value="<?php echo htmlspecialchars($customer['contact_person'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Vorname *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nachname *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Adresse -->
                        <h6 class="fw-bold mb-3 mt-4">Adresse</h6>
                        
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
                                <label for="city" class="form-label">Ort</label>
                                <input type="text" class="form-control" id="city" name="city"
                                       value="<?php echo htmlspecialchars($customer['city']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="country" class="form-label">Land</label>
                            <input type="text" class="form-control" id="country" name="country"
                                   value="<?php echo htmlspecialchars($customer['country']); ?>">
                        </div>
                        
                        <!-- Kontaktdaten -->
                        <h6 class="fw-bold mb-3 mt-4">Kontaktdaten</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Mobil</label>
                                <input type="tel" class="form-control" id="mobile" name="mobile"
                                       value="<?php echo htmlspecialchars($customer['mobile']); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fax" class="form-label">Fax</label>
                                <input type="tel" class="form-control" id="fax" name="fax"
                                       value="<?php echo htmlspecialchars($customer['fax'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-Mail</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($customer['email']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?php echo htmlspecialchars($customer['website']); ?>">
                        </div>
                        
                        <!-- Weitere Informationen -->
                        <h6 class="fw-bold mb-3 mt-4">Weitere Informationen</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tax_number" class="form-label">Steuernummer</label>
                                <input type="text" class="form-control" id="tax_number" name="tax_number"
                                       value="<?php echo htmlspecialchars($customer['tax_number']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="vat_id" class="form-label">USt-IdNr.</label>
                                <input type="text" class="form-control" id="vat_id" name="vat_id"
                                       value="<?php echo htmlspecialchars($customer['vat_id']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_terms" class="form-label">Zahlungsziel (Tage)</label>
                            <input type="number" class="form-control" id="payment_terms" name="payment_terms"
                                   value="<?php echo intval($customer['payment_terms'] ?? 30); ?>" min="0" max="180">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Anmerkungen</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="customers.php" class="btn btn-light border">
                                <i class="bi bi-arrow-left"></i> Abbrechen
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Kunden speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
