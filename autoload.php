<?php
// Wenn Composer verwendet wird
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Manuelle Installation
    require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';
}