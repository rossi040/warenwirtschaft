<?php
require_once 'config.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Aktuelle Zeit und Benutzer
$current_time = '2025-03-02 01:10:13';
$user_login = 'rossi040';
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
            
            <!-- Hamburger-Menü für kleine Bildschirme -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Hauptnavigation -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>" href="customers.php">Kunden</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'invoices.php' ? 'active' : '' ?>" href="invoices.php">Rechnungen</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'articles.php' ? 'active' : '' ?>" href="articles.php">Artikel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manufacturers.php' ? 'active' : '' ?>" href="manufacturers.php">Hersteller</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">Berichte</a>
                    </li>
                </ul>
                
                <!-- Datum und Benutzer auf der rechten Seite -->
                <div class="d-flex text-white">
                    <div class="me-3">
                        <small>UTC: <?php echo $current_time; ?></small>
                    </div>
                    <div>
                        <small>Benutzer: <?php echo htmlspecialchars($user_login); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </nav>