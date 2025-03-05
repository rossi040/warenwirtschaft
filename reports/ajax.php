<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

$response = ['error' => null, 'title' => '', 'content' => ''];

if (!isset($_GET['type'])) {
    $response['error'] = 'Kein Berichtstyp angegeben';
    echo json_encode($response);
    exit;
}

try {
    switch ($_GET['type']) {
        case 'monthly_revenue':
            $sql = "SELECT 
                        DATE_FORMAT(invoice_date, '%Y-%m') as month,
                        SUM(total_amount) as revenue,
                        LAG(SUM(total_amount)) OVER (ORDER BY DATE_FORMAT(invoice_date, '%Y-%m')) as prev_revenue
                    FROM invoices 
                    WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                    ORDER BY month DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['title'] = '<i class="bi bi-graph-up"></i> Umsatz pro Monat';
            $response['content'] = generateRevenueTable($data);
            break;

        case 'monthly_profit':
            $sql = "SELECT 
                        DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
                        SUM(i.total_amount) as revenue,
                        SUM(ii.quantity * a.cost_price) as costs,
                        SUM(i.total_amount) - SUM(ii.quantity * a.cost_price) as profit
                    FROM invoices i
                    JOIN invoice_items ii ON i.id = ii.invoice_id
                    JOIN articles a ON ii.article_id = a.id
                    WHERE i.invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
                    ORDER BY month DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['title'] = '<i class="bi bi-piggy-bank"></i> Gewinn pro Monat';
            $response['content'] = generateProfitTable($data);
            break;

        case 'bestsellers':
            $sql = "SELECT 
                        a.id as article_id,
                        a.name as article_name,
                        COUNT(DISTINCT i.id) as sale_count,
                        SUM(ii.quantity) as total_quantity,
                        SUM(ii.quantity * ii.price) as total_revenue
                    FROM articles a
                    JOIN invoice_items ii ON a.id = ii.article_id
                    JOIN invoices i ON ii.invoice_id = i.id
                    WHERE i.invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    GROUP BY a.id, a.name
                    ORDER BY total_quantity DESC
                    LIMIT 10";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['title'] = '<i class="bi bi-trophy"></i> Meistverkaufte Artikel';
            $response['content'] = generateBestsellersTable($data);
            break;

        default:
            $response['error'] = 'Ungültiger Berichtstyp';
    }
} catch(PDOException $e) {
    $response['error'] = 'Datenbankfehler: ' . $e->getMessage();
}

echo json_encode($response);
exit;

// Hilfsfunktionen für die Tabellengenerierung
function generateRevenueTable($data) {
    $html = '<div class="table-responsive"><table class="table table-striped">';
    $html .= '<thead><tr><th>Monat</th><th class="text-end">Umsatz</th><th class="text-end">Trend</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['month']) . '</td>';
        $html .= '<td class="text-end">' . number_format($row['revenue'], 2, ',', '.') . ' €</td>';
        $html .= '<td class="text-end">';
        if (isset($row['prev_revenue'])) {
            $trend = $row['revenue'] - $row['prev_revenue'];
            $icon = $trend >= 0 ? 'up' : 'down';
            $color = $trend >= 0 ? 'success' : 'danger';
            $html .= '<i class="bi bi-arrow-' . $icon . '-circle text-' . $color . '"></i>';
        }
        $html .= '</td></tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}

function generateProfitTable($data) {
    $html = '<div class="table-responsive"><table class="table table-striped">';
    $html .= '<thead><tr><th>Monat</th><th class="text-end">Umsatz</th><th class="text-end">Kosten</th>';
    $html .= '<th class="text-end">Gewinn</th><th class="text-end">Marge</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['month']) . '</td>';
        $html .= '<td class="text-end">' . number_format($row['revenue'], 2, ',', '.') . ' €</td>';
        $html .= '<td class="text-end">' . number_format($row['costs'], 2, ',', '.') . ' €</td>';
        $html .= '<td class="text-end">' . number_format($row['profit'], 2, ',', '.') . ' €</td>';
        $margin = ($row['revenue'] > 0) ? ($row['profit'] / $row['revenue'] * 100) : 0;
        $html .= '<td class="text-end">' . number_format($margin, 1, ',', '.') . '%</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}

function generateBestsellersTable($data) {
    $html = '<div class="table-responsive"><table class="table table-striped">';
    $html .= '<thead><tr><th>Rang</th><th>Artikel</th><th class="text-end">Verkäufe</th>';
    $html .= '<th class="text-end">Menge</th><th class="text-end">Umsatz</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($data as $index => $row) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['article_name']) . '</td>';
        $html .= '<td class="text-end">' . number_format($row['sale_count'], 0, ',', '.') . '</td>';
        $html .= '<td class="text-end">' . number_format($row['total_quantity'], 0, ',', '.') . '</td>';
        $html .= '<td class="text-end">' . number_format($row['total_revenue'], 2, ',', '.') . ' €</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}
?>