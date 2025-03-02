<?php
// add_sample_manufacturer.php
require_once 'config.php';

try {
    // Beispieldaten einfügen
    $stmt = $pdo->prepare("
        INSERT INTO manufacturers 
        (company_name, contact_person, phone, email, website, address, notes) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'Beispiel GmbH',
        'Max Mustermann',
        '+43 123 456789',
        'info@beispiel.at',
        'https://www.beispiel.at',
        'Musterstraße 1, 1010 Wien',
        'Wichtiger Partner für Elektronikteile'
    ]);
    
    $id = $pdo->lastInsertId();
    
    echo "<h2>Beispiel-Hersteller erfolgreich eingefügt (ID: $id)!</h2>";
    
    // Zweites Beispiel einfügen
    $stmt->execute([
        'Tech Solutions AG',
        'Anna Schmidt',
        '+43 987 654321',
        'kontakt@techsolutions.at',
        'https://www.techsolutions.at',
        'Technikweg 42, 4020 Linz',
        'Spezialist für Netzwerktechnik'
    ]);
    
    $id2 = $pdo->lastInsertId();
    echo "<h2>Zweiter Beispiel-Hersteller erfolgreich eingefügt (ID: $id2)!</h2>";
    
    // Überprüfen, ob die Daten richtig eingefügt wurden
    $stmt = $pdo->query("SELECT * FROM manufacturers");
    $manufacturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Aktuelle Hersteller in der Datenbank:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Firma</th><th>Kontaktperson</th><th>Telefon</th><th>E-Mail</th><th>Website</th></tr>";
    
    foreach ($manufacturers as $manufacturer) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($manufacturer['id']) . "</td>";
        echo "<td>" . htmlspecialchars($manufacturer['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($manufacturer['contact_person']) . "</td>";
        echo "<td>" . htmlspecialchars($manufacturer['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($manufacturer['email']) . "</td>";
        echo "<td>" . htmlspecialchars($manufacturer['website']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br><a href='manufacturers.php'>Zurück zur Herstellerübersicht</a>";
    
} catch (PDOException $e) {
    echo "Fehler beim Einfügen der Beispieldaten: " . $e->getMessage();
}
?>
