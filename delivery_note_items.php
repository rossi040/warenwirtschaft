<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Überprüfen, ob eine Lieferschein-ID übergeben wurde
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Keine Lieferschein-ID angegeben.';
    header('Location: delivery_notes.php');
    exit;
}

$delivery_note_id = intval($_GET['id']);
$errors = [];
$success_message = '';

// Lieferschein laden
$stmt = $pdo->prepare("
    SELECT dn.*, c.company_name, c.first_name, c.last_name 
    FROM delivery_notes dn
    LEFT JOIN customers c ON dn.customer_id = c.id
    WHERE dn.id = ?
");
$stmt->execute([$delivery_note_id]);
$delivery_note = $stmt->fetch();

if (!$delivery_note) {
    $_SESSION['error_message'] = 'Lieferschein nicht gefunden.';
    header('Location: delivery_notes.php');
    exit;
}

// Lieferscheinpositionen laden
$stmt = $pdo->prepare("
    SELECT di.*, a.description as article_description, a.article_number
    FROM delivery_note_items di
    LEFT JOIN articles a ON di.article_id = a.id
    WHERE di.delivery_note_id = ?
    ORDER BY di.position_number ASC
");
$stmt->execute([$delivery_note_id]);
$items = $stmt->fetchAll();

// Alle Artikel für das Dropdown-Menü laden
$stmt = $pdo->query("SELECT * FROM articles ORDER BY description ASC");
$articles = $stmt->fetchAll();

// Position hinzufügen
if (isset($_POST['add_item'])) {
    $article_id = $_POST['article_id'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $quantity = floatval(str_replace(',', '.', $_POST['quantity'] ?? '0'));
    
    // Validierung
    if (empty($description)) {
        $errors[] = 'Bitte geben Sie eine Beschreibung ein.';
    }
    
    if ($quantity <= 0) {
        $errors[] = 'Die Menge muss größer als 0 sein.';
    }
    
    if (empty($errors)) {
        try {
            // Nächste Positionsnummer ermitteln
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(position_number), 0) + 1 AS next_pos 
                FROM delivery_note_items 
                WHERE delivery_note_id = ?
            ");
            $stmt->execute([$delivery_note_id]);
            $position_number = $stmt->fetch()['next_pos'];
            
            // Position einfügen
            $stmt = $pdo->prepare("
                INSERT INTO delivery_note_items 
                (delivery_note_id, position_number, article_id, description, quantity, created_at, created_by) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $delivery_note_id,
                $position_number,
                $article_id ?: null,
                $description,
                $quantity,
                $_SESSION['user_id']
            ]);
            
            $success_message = 'Position wurde erfolgreich hinzugefügt.';
            
            // Seite neu laden, um aktualisierte Daten anzuzeigen
            header("Location: delivery_note_items.php?id=$delivery_note_id&success=1");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}

// Position aktualisieren
if (isset($_POST['update_item'])) {
    $item_id = intval($_POST['item_id']);
    $description = trim($_POST['description']);
    $quantity = floatval(str_replace(',', '.', $_POST['quantity']));
    
    // Validierung
    if (empty($description)) {
        $errors[] = 'Bitte geben Sie eine Beschreibung ein.';
    }
    
    if ($quantity <= 0) {
        $errors[] = 'Die Menge muss größer als 0 sein.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE delivery_note_items 
                SET description = ?, quantity = ?, updated_at = NOW(), updated_by = ? 
                WHERE id = ? AND delivery_note_id = ?
            ");
            $stmt->execute([
                $description,
                $quantity,
                $_SESSION['user_id'],
                $item_id,
                $delivery_note_id
            ]);
            
            $success_message = 'Position wurde erfolgreich aktualisiert.';
            
            // Seite neu laden, um aktualisierte Daten anzuzeigen
            header("Location: delivery_note_items.php?id=$delivery_note_id&success=2");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}

// Position löschen
if (isset($_POST['delete_item'])) {
    $item_id = intval($_POST['item_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM delivery_note_items WHERE id = ? AND delivery_note_id = ?");
        $stmt->execute([$item_id, $delivery_note_id]);
        
        // Positionen neu nummerieren
        $stmt = $pdo->prepare("
            SELECT id FROM delivery_note_items 
            WHERE delivery_note_id = ? 
            ORDER BY position_number ASC
        ");
        $stmt->execute([$delivery_note_id]);
        $item_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($item_ids as $index => $id) {
            $position = $index + 1;
            $stmt = $pdo->prepare("UPDATE delivery_note_items SET position_number = ? WHERE id = ?");
            $stmt->execute([$position, $id]);
        }
        
        $success_message = 'Position wurde erfolgreich gelöscht.';
        
        // Seite neu laden, um aktualisierte Daten anzuzeigen
        header("Location: delivery_note_items.php?id=$delivery_note_id&success=3");
        exit;
        
    } catch (PDOException $e) {
        $errors[] = 'Datenbankfehler: ' . $e->getMessage();
    }
}

// Positionen sortieren
if (isset($_POST['move_up']) || isset($_POST['move_down'])) {
    $item_id = isset($_POST['move_up']) ? intval($_POST['move_up']) : intval($_POST['move_down']);
    $direction = isset($_POST['move_up']) ? 'up' : 'down';
    
    try {
        // Aktuelle Position ermitteln
        $stmt = $pdo->prepare("
            SELECT id, position_number 
            FROM delivery_note_items 
            WHERE id = ? AND delivery_note_id = ?
        ");
        $stmt->execute([$item_id, $delivery_note_id]);
        $current_item = $stmt->fetch();
        
        if ($current_item) {
            if ($direction === 'up' && $current_item['position_number'] > 1) {
                // Nach oben verschieben: mit der Position darüber tauschen
                $swap_position = $current_item['position_number'] - 1;
                
                // ID des Eintrags mit der Tauschposition ermitteln
                $stmt = $pdo->prepare("
                    SELECT id FROM delivery_note_items 
                    WHERE delivery_note_id = ? AND position_number = ?
                ");
                $stmt->execute([$delivery_note_id, $swap_position]);
                $swap_item_id = $stmt->fetchColumn();
                
                if ($swap_item_id) {
                    // Positionen tauschen
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        UPDATE delivery_note_items SET position_number = ? WHERE id = ?
                    ");
                    $stmt->execute([0, $current_item['id']]); // Temporäre Position
                    $stmt->execute([$current_item['position_number'], $swap_item_id]);
                    $stmt->execute([$swap_position, $current_item['id']]);
                    
                    $pdo->commit();
                }
            } elseif ($direction === 'down') {
                // Nach unten verschieben: mit der Position darunter tauschen
                $swap_position = $current_item['position_number'] + 1;
                
                // ID des Eintrags mit der Tauschposition ermitteln
                $stmt = $pdo->prepare("
                    SELECT id FROM delivery_note_items 
                    WHERE delivery_note_id = ? AND position_number = ?
                ");
                $stmt->execute([$delivery_note_id, $swap_position]);
                $swap_item_id = $stmt->fetchColumn();
                
                if ($swap_item_id) {
                    // Positionen tauschen
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        UPDATE delivery_note_items SET position_number = ? WHERE id = ?
                    ");
                    $stmt->execute([0, $current_item['id']]); // Temporäre Position
                    $stmt->execute([$current_item['position_number'], $swap_item_id]);
                    $stmt->execute([$swap_position, $current_item['id']]);
                    
                    $pdo->commit();
                }
            }
            
            // Seite neu laden, um aktualisierte Daten anzuzeigen
            header("Location: delivery_note_items.php?id=$delivery_note_id");
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = 'Datenbankfehler: ' . $e->getMessage();
    }
}

// Erfolgsmeldung aus URL-Parameter
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case '1':
            $success_message = 'Position wurde erfolgreich hinzugefügt.';
            break;
        case '2':
            $success_message = 'Position wurde erfolgreich aktualisiert.';
            break;
        case '3':
            $success_message = 'Position wurde erfolgreich gelöscht.';
            break;
    }
}

// Lieferscheinpositionen erneut laden
$stmt = $pdo->prepare("
    SELECT di.*, a.description as article_description, a.article_number
    FROM delivery_note_items di
    LEFT JOIN articles a ON di.article_id = a.id
    WHERE di.delivery_note_id = ?
    ORDER BY di.position_number ASC
");
$stmt->execute([$delivery_note_id]);
$items = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-truck"></i> Lieferscheinpositionen</h1>
                <div>
                    <a href="delivery_note_pdf.php?id=<?php echo $delivery_note_id; ?>" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF anzeigen
                    </a>
                    <a href="delivery_note_edit.php?id=<?php echo $delivery_note_id; ?>" class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left"></i> Zurück zum Lieferschein
                    </a>
                </div>
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
            <?php