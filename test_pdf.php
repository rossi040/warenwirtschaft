<?php
// Direkte Einbindung von TCPDF
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

try {
    // PDF erstellen
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    
    // Metadaten
    $pdf->SetCreator('Test');
    $pdf->SetAuthor('Test');
    $pdf->SetTitle('Test PDF');
    
    // Seite hinzufügen
    $pdf->AddPage();
    
    // Text hinzufügen
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'TCPDF funktioniert!', 0, 1, 'C');
    
    // PDF ausgeben
    $pdf->Output('test.pdf', 'I');
    
} catch (Exception $e) {
    echo 'Fehler: ' . $e->getMessage();
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
?>
