<?php
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentDateTime() {
    return gmdate('Y-m-d H:i:s');
}

function getCurrentUser() {
    return $_SESSION['username'] ?? 'Nicht angemeldet';
}