<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            header('Location: index.php');
            exit();
        } else {
            $error = 'Ungültige Zugangsdaten';
        }
    } else {
        $error = 'Bitte füllen Sie alle Felder aus';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rechnungsverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form method="POST">
            <h1 class="h3 mb-3 fw-normal">Rechnungsverwaltung</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Benutzername" required>
                <label for="username">Benutzername</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Passwort" required>
                <label for="password">Passwort</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Anmelden</button>
        </form>
    </main>
</body>
</html>
