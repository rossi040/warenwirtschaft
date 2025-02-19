<?php
require_once 'autoload.php';
require_once 'includes/config.php';

use TCPDF;

if (!isset($_GET['id'])) {
    die('Keine Rechnungs-ID angegeben');
}

try {
    // Rechnungsdaten laden
    $stmt = $pdo->prepare("
        SELECT i.*, c.* 
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die('Rechnung nicht gefunden');
    }

    // Rechnungspositionen laden
    $stmt = $pdo->prepare("
        SELECT i.*, a.name as article_name
        FROM invoice_items i
        LEFT JOIN articles a ON i.article_id = a.id
        WHERE i.invoice_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $items = $stmt->fetchAll();

    // PDF erstellen
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

    // PDF Metadaten
    $pdf->SetCreator('Rechnungsverwaltung');
    $pdf->SetAuthor('Ihr Unternehmen');
    $pdf->SetTitle('Rechnung ' . $invoice['invoice_number']);

    // Erste Seite
    $pdf->AddPage();

    // Header
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Rechnung', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Rechnungsnummer: ' . $invoice['invoice_number'], 0, 1, 'L');
    $pdf->Cell(0, 10, 'Datum: ' . date('d.m.Y', strtotime($invoice['invoice_date'])), 0, 1, 'L');

    // Absender
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 10, 'Ihr Unternehmen', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Musterstraße 1', 0, 1, 'L');
    $pdf->Cell(0, 5, '12345 Musterstadt', 0, 1, 'L');

    // Empfänger
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 10, ($invoice['company_name'] ?: $invoice['first_name'] . ' ' . $invoice['last_name']), 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $invoice['street'], 0, 1, 'L');
    $pdf->Cell(0, 5, $invoice['zip'] . ' ' . $invoice['city'], 0, 1, 'L');

    // Rechnungspositionen
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 7, 'Artikel', 1, 0, 'L');
    $pdf->Cell(25, 7, 'Menge', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Preis', 1, 0, 'R');
    $pdf->Cell(25, 7, 'MwSt', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Gesamt', 1, 1, 'R');

    $pdf->SetFont('helvetica', '', 10);
    $total = 0;
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $total += $itemTotal;

        $pdf->Cell(90, 7, $item['article_name'], 1, 0, 'L');
        $pdf->Cell(25, 7, $item['quantity'], 1, 0, 'R');
        $pdf->Cell(25, 7, number_format($item['price'], 2, ',', '.') . ' €', 1, 0, 'R');
        $pdf->Cell(25, 7, $item['vat_rate'] . '%', 1, 0, 'R');
        $pdf->Cell(25, 7, number_format($itemTotal, 2, ',', '.') . ' €', 1, 1, 'R');
    }

    // Gesamtsumme
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(165, 7, 'Gesamtsumme:', 1, 0, 'R');
    $pdf->Cell(25, 7, number_format($total, 2, ',', '.') . ' €', 1, 1, 'R');

    // Bemerkungen
    if (!empty($invoice['notes'])) {
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'Bemerkungen:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 7, $invoice['notes'], 0, 'L');
    }

    // PDF ausgeben
    $pdf->Output('Rechnung_' . $invoice['invoice_number'] . '.pdf', 'D');

} catch (Exception $e) {
    die('Fehler bei der PDF-Erstellung: ' . $e->getMessage());
}
?>
