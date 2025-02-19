<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentDateTime = '2025-02-18 22:03:08';  // Neue aktualisierte Zeit
$currentUser = 'rossi040';                 // Beibehaltener Benutzer

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnungsverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Weinvierterl</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">
                            <i class="bi bi-people"></i> Kunden
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">
                            <i class="bi bi-file-text"></i> Rechnungen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="articles.php">
                            <i class="bi bi-box"></i> Artikel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Berichte
                        </a>
                    </li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <div class="me-3">
                        <small>Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted): <?php echo $currentDateTime; ?></small>
                    </div>
                    <div class="me-3">
                        <small>Current User's Login: <?php echo htmlspecialchars($currentUser); ?></small>
                    </div>
                    <div>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
