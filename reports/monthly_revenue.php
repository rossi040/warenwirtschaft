<?php
$base_path = '/rechnungsverwaltung';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/header.php';

// Aktuelle System-Werte
$current_utc = '2025-02-20 07:18:19';
$current_user = 'rossi040';

// Rest des Codes bleibt gleich...

?>

<!-- Am Ende der Datei -->
<div class="mt-3">
    <a href="<?php echo $base_path; ?>/berichte.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
    </a>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . $base_path . '/includes/footer.php'; ?>