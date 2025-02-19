<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Kunden laden
$stmt = $pdo->query("SELECT * FROM customers ORDER BY company_name, last_name, first_name");
$customers = $stmt->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-people"></i> Kunden</h1>
                <a href="customer_edit.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Neuer Kunde
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Firma</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td>
                                        <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="customer_delete.php?id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Wirklich löschen?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
