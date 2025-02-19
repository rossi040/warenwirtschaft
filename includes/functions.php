<?php
// Hilfsfunktionen

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

function getTodaysSales() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    return number_format($stmt->fetch(PDO::FETCH_ASSOC)['total'], 2);
}

function getOpenInvoices() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM invoices 
        WHERE status = 'draft' OR status = 'sent'
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getOrderableItems() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM articles 
        WHERE orderable = 1 AND stock = 0
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>