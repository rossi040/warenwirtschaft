<?php
require_once 'includes/config.php';

// Prüfen ob Benutzer eingeloggt ist
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Nicht eingeloggt']));
}

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'error' => 'Nur POST-Requests sind erlaubt']));
}

// JSON-Daten empfangen
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['invoice_id']) || !isset($data['positions'])) {
    die(json_encode(['success' => false, 'error' => 'Ungültige Daten']));
}

try {
    $pdo->beginTransaction();

    // Prüfen ob die Rechnung existiert
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$data['invoice_id']]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        throw new Exception('Rechnung nicht gefunden');
    }

    // Alle bestehenden Positionen für diese Rechnung löschen
    $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$data['invoice_id']]);

    // Neue Positionen einfügen
    $stmt = $pdo->prepare("
        INSERT INTO invoice_items (invoice_id, article_id, quantity, price_per_unit, total_price) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $subtotal = 0;

    foreach ($data['positions'] as $position) {
        // Artikel-Daten prüfen
        $checkStmt = $pdo->prepare("SELECT d, description FROM articles WHERE d = ?");
        $checkStmt->execute([$position['article_id']]);
        $article = $checkStmt->fetch();

        if (!$article) {
            throw new Exception("Artikel mit ID {$position['article_id']} nicht gefunden");
        }

        $total_price = $position['quantity'] * $position['price_per_unit'];
        $stmt->execute([
            $data['invoice_id'],
            $position['article_id'],
            $position['quantity'],
            $position['price_per_unit'],
            $total_price
        ]);
        $subtotal += $total_price;
    }

    // Rechnungssummen aktualisieren
    $vat_rate = $invoice['vat_rate'];
    $vat_amount = $subtotal * ($vat_rate / 100);
    $total_amount = $subtotal + $vat_amount;

    // Rechnung aktualisieren
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET subtotal = ?,
            vat_amount = ?,
            total_amount = ?,
            updated_at = CURRENT_TIMESTAMP,
            last_modified_by = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $subtotal,
        $vat_amount,
        $total_amount,
        $_SESSION['user_id'],
        $data['invoice_id']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Rechnungspositionen wurden erfolgreich gespeichert',
        'data' => [
            'subtotal' => $subtotal,
            'vat_amount' => $vat_amount,
            'total_amount' => $total_amount
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Speichern: ' . $e->getMessage()
    ]);
}
