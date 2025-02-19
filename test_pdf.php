<?php
require_once 'autoload.php';

// TCPDF Instanz erstellen
$pdf = new TCPDF();

// Neue Seite hinzufügen
$pdf->AddPage();

// Text hinzufügen
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'PDF Test - Wenn Sie diese Nachricht sehen, funktioniert TCPDF!', 0, 1);

// PDF ausgeben
$pdf->Output('test.pdf', 'D');