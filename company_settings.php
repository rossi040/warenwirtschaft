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

// Firmendaten laden
$stmt = $pdo->query("SELECT * FROM company_settings ORDER BY id LIMIT 1");
$company = $stmt->fetch();

// Wenn keine Firmendaten vorhanden sind, erstelle einen leeren Datensatz
if (!$company) {
    $pdo->query("INSERT INTO company_settings (company_name, city, street, zip_code, country, created_by) VALUES ('Meine Firma', 'Musterstadt', 'Hauptstraße', '12345', 'Deutschland', ".$_SESSION['user_id'].")");
    $stmt = $pdo->query("SELECT * FROM company_settings ORDER BY id LIMIT 1");
    $company = $stmt->fetch();
}

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular übernehmen
        $company_name = trim($_POST['company_name'] ?? '');
        $legal_form = trim($_POST['legal_form'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $house_number = trim($_POST['house_number'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? 'Deutschland');
        $phone = trim($_POST['phone'] ?? '');
        $fax = trim($_POST['fax'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        $vat_id = trim($_POST['vat_id'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $bic = trim($_POST['bic'] ?? '');
        $footer_text = trim($_POST['footer_text'] ?? '');
        $invoice_notes = trim($_POST['invoice_notes'] ?? '');
        $delivery_note_notes = trim($_POST['delivery_note_notes'] ?? '');
        $default_vat_rate = floatval(str_replace(',', '.', $_POST['default_vat_rate'] ?? 19));
        $default_payment_terms = intval($_POST['default_payment_terms'] ?? 30);
        
        // Validierung
        if (empty($company_name)) {
            throw new Exception('Bitte geben Sie den Firmennamen ein.');
        }
        
        if (empty($street) || empty($zip_code) || empty($city)) {
            throw new Exception('Bitte geben Sie die vollständige Adresse ein.');
        }
        
        // Logo-Upload
        $logo_path = $company['logo_path'];
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['logo']['type'], $allowed_types)) {
        throw new Exception('Ungültiges Dateiformat. Erlaubt sind JPEG, PNG und GIF.');
    }
    
    if ($_FILES['logo']['size'] > $max_size) {
        throw new Exception('Die Datei ist zu groß. Maximale Größe beträgt 2MB.');
    }
    
    // Zielverzeichnis erstellen, falls nicht vorhanden
    $upload_dir = 'uploads/logos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Dateiname generieren
    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $new_filename = 'logo_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $new_filename;
    
    // Datei verschieben
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
        // Altes Logo löschen, falls vorhanden
        if ($logo_path && file_exists($logo_path)) {
            unlink($logo_path);
        }
        $logo_path = $target_path;
    } else {
        throw new Exception('Fehler beim Hochladen der Datei.');
    }
}
        
        // Daten in der Datenbank aktualisieren
        $stmt = $pdo->prepare("
            UPDATE company_settings SET
                company_name = ?,
                legal_form = ?,
                street = ?,
                house_number = ?,
                zip_code = ?,
                city = ?,
                country = ?,
                phone = ?,
                fax = ?,
                email = ?,
                website = ?,
                tax_number = ?,
                vat_id = ?,
                bank_name = ?,
                iban = ?,
                bic = ?,
                logo_path = ?,
                footer_text = ?,
                invoice_notes = ?,
                delivery_note_notes = ?,
                default_vat_rate = ?,
                default_payment_terms = ?,
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $company_name,
            $legal_form,
            $street,
            $house_number,
            $zip_code,
            $city,
            $country,
            $phone,
            substr($fax, 0, 20), // Fax auf max. 20 Zeichen beschränken
            $email,
            $website,
            $tax_number,
            $vat_id,
            $bank_name,
            $iban,
            $bic,
            $logo_path,
            $footer_text,
            $invoice_notes,
            $delivery_note_notes,
            $default_vat_rate,
            $default_payment_terms,
            $_SESSION['user_id'],
            $company['id']
        ]);
        
        $success_message = 'Die Firmendaten wurden erfolgreich aktualisiert.';
        
        // Aktualisierte Daten laden
        $stmt = $pdo->query("SELECT * FROM company_settings WHERE id = " . $company['id']);
        $company = $stmt->fetch();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-building"></i> Firmendaten</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zum Dashboard
                </a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Firmendaten bearbeiten</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <ul class="nav nav-tabs mb-4" id="companyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                    Allgemein
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="banking-tab" data-bs-toggle="tab" data-bs-target="#banking" type="button">
                                    Bankverbindung
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button">
                                    Dokumente
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                                    Einstellungen
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="companyTabsContent">
                            <!-- Allgemeine Informationen -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label for="company_name" class="form-label">Firmenname *</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                                       value="<?php echo htmlspecialchars($company['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="legal_form" class="form-label">Rechtsform</label>
                                                <input type="text" class="form-control" id="legal_form" name="legal_form" 
                                                       value="<?php echo htmlspecialchars($company['legal_form'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label for="street" class="form-label">Straße *</label>
                                                <input type="text" class="form-control" id="street" name="street" 
                                                       value="<?php echo htmlspecialchars($company['street'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="house_number" class="form-label">Hausnummer</label>
                                                <input type="text" class="form-control" id="house_number" name="house_number" 
                                                       value="<?php echo htmlspecialchars($company['house_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="zip_code" class="form-label">PLZ *</label>
                                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                                       value="<?php echo htmlspecialchars($company['zip_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-md-8">
                                                <label for="city" class="form-label">Ort *</label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo htmlspecialchars($company['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="country" class="form-label">Land</label>
                                            <input type="text" class="form-control" id="country" name="country" 
                                                   value="<?php echo htmlspecialchars($company['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Telefon</label>
                                                <input type="text" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($company['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fax" class="form-label">Fax</label>
                                                <input type="text" class="form-control" id="fax" name="fax" 
                                                       value="<?php echo htmlspecialchars($company['fax'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">E-Mail</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($company['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="website" class="form-label">Website</label>
                                                <input type="text" class="form-control" id="website" name="website" 
                                                       value="<?php echo htmlspecialchars($company['website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="tax_number" class="form-label">Steuernummer</label>
                                                <input type="text" class="form-control" id="tax_number" name="tax_number" 
                                                       value="<?php echo htmlspecialchars($company['tax_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="vat_id" class="form-label">USt-IdNr.</label>
                                                <input type="text" class="form-control" id="vat_id" name="vat_id" 
                                                       value="<?php echo htmlspecialchars($company['vat_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="logo" class="form-label">Firmenlogo</label>
                                            <?php if ($company['logo_path']): ?>
                                                <div class="mb-2 text-center">
                                                    <img src="<?php echo htmlspecialchars($company['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                         alt="Firmenlogo" class="img-thumbnail" style="max-height: 150px;">
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                            <small class="text-muted">Empfohlene Größe: 300x100 Pixel, max. 2MB (JPG, PNG, GIF)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bankverbindung -->
                            <div class="tab-pane fade" id="banking" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="bank_name" class="form-label">Kreditinstitut</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                               value="<?php echo htmlspecialchars($company['bank_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="iban" class="form-label">IBAN</label>
                                        <input type="text" class="form-control" id="iban" name="iban" 
                                               value="<?php echo htmlspecialchars($company['iban'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="bic" class="form-label">BIC</label>
                                        <input type="text" class="form-control" id="bic" name="bic" 
                                               value="<?php echo htmlspecialchars($company['bic'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dokumente -->
                            <div class="tab-pane fade" id="documents" role="tabpanel">
                                <div class="mb-3">
                                    <label for="invoice_notes" class="form-label">Standardtext für Rechnungen</label>
                                    <textarea class="form-control" id="invoice_notes" name="invoice_notes" rows="3"><?php echo htmlspecialchars($company['invoice_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <small class="text-muted">Dieser Text erscheint auf allen Rechnungen.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="delivery_note_notes" class="form-label">Standardtext für Lieferscheine</label>
                                    <textarea class="form-control" id="delivery_note_notes" name="delivery_note_notes" rows="3"><?php echo htmlspecialchars($company['delivery_note_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="footer_text" class="form-label">Fußzeile für Dokumente</label>
                                    <textarea class="form-control" id="footer_text" name="footer_text" rows="3"><?php echo htmlspecialchars($company['footer_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <small class="text-muted">Dieser Text erscheint als Fußzeile auf allen Dokumenten.</small>
                                </div>
                            </div>
                            
                          <!-- Einstellungen -->
<div class="tab-pane fade" id="settings" role="tabpanel">
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="default_vat_rate" class="form-label">Standard-Mehrwertsteuersatz (%)</label>
            <input type="text" class="form-control" id="default_vat_rate" name="default_vat_rate"
                   value="<?php echo number_format($company['default_vat_rate'] ?? 19.00, 2, ',', '.'); ?>">
        </div>
        <div class="col-md-6">
            <label for="default_payment_terms" class="form-label">Standard-Zahlungsziel (Tage)</label>
            <input type="number" class="form-control" id="default_payment_terms" name="default_payment_terms"
                   value="<?php echo intval($company['default_payment_terms'] ?? 30); ?>">
        </div>
    </div>
</div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> Firmendaten speichern
    </button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
