<?php
require_once 'config.php';
require_once 'header.php';

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Standardwerte für neuen Artikel
$product = [
    'product_id' => '',
    'name' => '',
    'description' => '',
    'price' => '0.00',
    'tax_rate' => '0.00'
];

// Abrufen des Artikels, wenn ID übergeben wurde
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $db_product = $stmt->fetch();
        
        if ($db_product) {
            $product = $db_product;
        } else {
            $_SESSION['error'] = "Artikel nicht gefunden";
            header('Location: products.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Datenbankfehler: " . $e->getMessage();
        header('Location: products.php');
        exit();
    }
}

// Verarbeitung des Formulars beim Absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validierung
        if (empty($_POST['name'])) {
            throw new Exception("Bitte geben Sie einen Namen für den Artikel an.");
        }
        
        // Speichern der Daten
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'] ?: 0,
            'tax_rate' => $_POST['tax_rate'] ?: 0
        ];
        
        if (empty($product['product_id'])) {
            // Neuen Artikel erstellen
            $sql = "INSERT INTO products (name, description, price, tax_rate)
                    VALUES (:name, :description, :price, :tax_rate)";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            $_SESSION['success'] = "Artikel wurde erfolgreich erstellt.";
            header('Location: products.php');
            exit();
        } else {
            // Bestehenden Artikel aktualisieren
            $sql = "UPDATE products SET 
                    name = :name,
                    description = :description,
                    price = :price,
                    tax_rate = :tax_rate
                    WHERE product_id = :product_id";
                    
            $data['product_id'] = $product['product_id'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            $_SESSION['success'] = "Artikel wurde erfolgreich aktualisiert.";
            header('Location: products.php');
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        
        // Fehlerhafte Formulardaten wiederherstellen
        $product = array_merge($product, $_POST);
    }
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= empty($product['product_id']) ? 'Neuen Artikel erstellen' : 'Artikel bearbeiten' ?></h2>
        <a href="products.php" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Abbrechen
        </a>
    </div>

    <?php if (isset($_SESSION['error']))): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3