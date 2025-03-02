<?php
require_once 'includes/config.php';

// TCPDF einbinden
require_once 'vendor/autoload.php'; // Für Composer-Installation
// ODER direkten Pfad verwenden, falls nicht über Composer installiert:
// require_once 'path/to/tcpdf/tcpdf.php';

use TCPDF;

if (!isset($_GET['id'])) {
    die('Keine Rechnungs-ID angegeben');
}

try {
    // Rechnungsdaten laden
    $stmt = $pdo->prepare("
        SELECT i.*, 
               c.company_name, c.first_name, c.last_name, 
               c.street, c.house_number, c.zip_code, c.city
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
        SELECT i.*, a.description as article_description 
        FROM invoice_items i
        LEFT JOIN articles a ON i.article_id = a.id
        WHERE i.invoice_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $items = $stmt->fetchAll();

    // PDF erstellen
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

    // PDF Einstellungen
    $pdf->SetCreator('Warenwirtschaft');
    $pdf->SetAuthor($currentUser); // Aus config.php
    $pdf->SetTitle('Rechnung ' . $invoice['invoice_number']);
    $pdf->SetSubject('Rechnung');
    
    // Seitenränder (links, oben, rechts)
    $pdf->SetMargins(15, 15, 15);
    
    // Header und Footer deaktivieren
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Automatische Seitenumbrüche
    $pdf->SetAutoPageBreak(true, 15);

    // Seite hinzufügen
    $pdf->AddPage();

    // Firmenlogo einfügen, falls vorhanden
    // $pdf->Image('path/to/logo.png', 15, 15, 50, '', 'PNG');

    // Rechnungstitel und -details
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Rechnung', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Rechnungsnummer: ' . $invoice['invoice_number'], 0, 1, 'R');
    $pdf->Cell(0, 6, 'Datum: ' . date('d.m.Y', strtotime($invoice['invoice_date'])), 0, 1, 'R');
    $pdf->Cell(0, 6, 'Fällig bis: ' . date('d.m.Y', strtotime($invoice['due_date'])), 0, 1, 'R');

    // Absender
    $pdf->SetY(50);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Ihr Unternehmen GmbH', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Musterstraße 123', 0, 1, 'L');
    $pdf->Cell(0, 5, '12345 Musterstadt', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Tel: +49 123 4567890', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Email: kontakt@ihrunternehmen.de', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Web: www.ihrunternehmen.de', 0, 1, 'L');

    // Empfänger
    $pdf->SetY(50);
    $pdf->SetX(120);
    $pdf->SetFont('helvetica', 'B', 10);

    // Korrekte Kundenanrede basierend auf verfügbaren Daten
    $customerName = '';
    if (!empty($invoice['company_name'])) {
        $customerName = $invoice['company_name'];
    } else {
        $customerName = $invoice['first_name'] . ' ' . $invoice['last_name'];
    }
    $pdf->Cell(0, 6, $customerName, 0, 1, 'L');
    
    $pdf->SetX(120);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $invoice['street'] . ' ' . $invoice['house_number'], 0, 1, 'L');
    $pdf->SetX(120);
    $pdf->Cell(0, 5, $invoice['zip_code'] . ' ' . $invoice['city'], 0, 1, 'L');

    // Abstand vor Tabelle
    $pdf->Ln(20);

    // Tabellenkopf für Rechnungspositionen
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(90, 10, 'Beschreibung', 1, 0, 'L', true);
    $pdf->Cell(20, 10, 'Menge', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Einzelpreis', 1, 0, 'R', true);
    $pdf->Cell(35, 10, 'Betrag', 1, 1, 'R', true);

    // Rechnungspositionen
    $pdf->SetFont('helvetica', '', 9);
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price_per_unit'];
        
        // Multi-Zeilen-Text, wenn die Beschreibung zu lang ist
        $pdf->MultiCell(90, 10, $item['article_description'], 1, 'L', false, 0);
        
        $pdf->Cell(20, 10, number_format($item['quantity'], 2, ',', '.'), 1, 0, 'C');
        $pdf->Cell(30, 10, number_format($item['price_per_unit'], 2, ',', '.') . ' €', 1, 0, 'R');
        $pdf->Cell(35, 10, number_format($itemTotal, 2, ',', '.') . ' €', 1, 1, 'R');
    }

    // Zusammenfassung
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(140, 10, 'Zwischensumme', 1, 0, 'R', true);
    $pdf->Cell(35, 10, number_format($invoice['subtotal'], 2, ',', '.') . ' €', 1, 1, 'R', true);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(140, 8, 'MwSt. (' . $invoice['vat_rate'] . '%)', 1, 0, 'R');
    $pdf->Cell(35, 8, number_format($invoice['vat_amount'], 2, ',', '.') . ' €', 1, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 10, 'Gesamtbetrag', 1, 0, 'R', true);
    $pdf->Cell(35, 10, number_format($invoice['total_amount'], 2, ',', '.') . ' €', 1, 1, 'R', true);

    // Zahlungsinformationen
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Zahlungsinformationen', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Bitte überweisen Sie den Gesamtbetrag bis zum ' . date('d.m.Y', strtotime($invoice['due_date'])), 0, 1, 'L');
    $pdf->Cell(0, 5, 'Bankverbindung: Sparkasse Musterstadt', 0, 1, 'L');
    $pdf->Cell(0, 5, 'IBAN: DE12 3456 7890 1234 5678 90', 0, 1, 'L');
    $pdf->Cell(0, 5, 'BIC: SPKADE1XXX', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Verwendungszweck: ' . $invoice['invoice_number'], 0, 1, 'L');

    // Fußzeile mit Geschäftsdaten
    $pdf->SetY(-40);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, 'Ihr Unternehmen GmbH - Musterstraße 123 - 12345 Musterstadt', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Steuernummer: 123/456/78901 - USt-IdNr.: DE123456789', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Geschäftsführer: Max Mustermann - Amtsgericht Musterstadt HRB 12345', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Erstellt am: ' . date('d.m.Y H:i', strtotime($currentDateTime)) . ' - Erstellt durch: ' . $currentUser, 0, 1, 'C');

    // Ausgabe des PDFs
    $pdf->Output('Rechnung_' . $invoice['invoice_number'] . '.pdf', 'I'); // 'I' für inline anzeigen im Browser
    exit;

} catch (Exception $e) {
    die('Fehler bei der PDF-Erstellung: ' . $e->getMessage());
}
?>
