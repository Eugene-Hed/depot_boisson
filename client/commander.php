
<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../index.php');
    exit();
}

$depot_id = $_GET['depot'] ?? null;
if (!$depot_id || empty($_SESSION['cart'][$depot_id])) {
    header('Location: produits.php?depot=' . $depot_id);
    exit();
}

// Fetch depot info
$sql = "SELECT * FROM depots WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $depot_id]);
$depot = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate cart total
$cart_total = 0;
foreach ($_SESSION['cart'][$depot_id] as $item) {
    $cart_total += $item['prix'] * $item['quantite'];
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Create order
        $sql = "INSERT INTO commandeclient (id_client, date_commande, total, statut, id_utilisateur, id_depot) 
                VALUES (:client_id, NOW(), :total, 'En attente', :user_id, :depot_id)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':client_id' => $_SESSION['client_id'],
            ':total' => $cart_total,
            ':user_id' => $_SESSION['user_id'],
            ':depot_id' => $depot_id
        ]);
        
        $commande_id = $conn->lastInsertId();

        // Add order details without updating stock
        foreach ($_SESSION['cart'][$depot_id] as $product_id => $item) {
            $sql = "INSERT INTO detailscommandeclient (id_commande, id_produit, quantite, prix_unit) 
                    VALUES (:commande_id, :product_id, :quantite, :prix)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':commande_id' => $commande_id,
                ':product_id' => $product_id,
                ':quantite' => $item['quantite'],
                ':prix' => $item['prix']
            ]);
        }

        $conn->commit();
        unset($_SESSION['cart'][$depot_id]);
        header('Location: commandes.php?success=1');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Une erreur est survenue lors de la commande";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser la commande - <?php echo htmlspecialchars($depot['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Récapitulatif de la commande</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'][$depot_id] as $product_id => $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['nom']); ?></td>
                                            <td><?php echo $item['quantite']; ?></td>
                                            <td><?php echo number_format($item['prix'], 0, ',', ' '); ?> FCFA</td>
                                            <td><?php echo number_format($item['prix'] * $item['quantite'], 0, ',', ' '); ?> FCFA</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th><?php echo number_format($cart_total, 0, ',', ' '); ?> FCFA</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Finaliser la commande</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Dépôt:</strong> <?php echo htmlspecialchars($depot['nom']); ?><br>
                            <strong>Total à payer:</strong> <?php echo number_format($cart_total, 0, ',', ' '); ?> FCFA
                        </p>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Confirmer la commande
                            </button>
                            <a href="produits.php?depot=<?php echo $depot_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Retour aux produits
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
