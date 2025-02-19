<?php
require_once 'config.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Exakte Zeit und Benutzer wie spezifiziert
$current_time = '2025-02-18 20:14:41';  // Exakte Zeit wie angegeben
$user_login = 'rossi040';               // Exakter Benutzer
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnungsverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Rechnungsverwaltung</a>
            <div class="d-flex text-white">
                <div class="me-3">
                    <small>UTC: <?php echo $current_time; ?></small>
                </div>
                <div>
                    <small>Benutzer: <?php echo htmlspecialchars($user_login); ?></small>
                </div>
            </div>
        </div>
    </nav>
