<?php
require_once '../includes/database.php';
session_write_close();
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../index.php');
    exit();
}

// Fetch available depots
$sql = "SELECT * FROM depots ORDER BY nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$depots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent orders
$sql = "SELECT cc.*, d.nom as depot_nom 
        FROM commandeclient cc 
        JOIN depots d ON cc.id_depot = d.id 
        WHERE cc.id_client = :client_id 
        ORDER BY cc.date_commande DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute([':client_id' => $_SESSION['client_id']]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total orders count
$sql = "SELECT COUNT(*) as total FROM commandeclient WHERE id_client = :client_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':client_id' => $_SESSION['client_id']]);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total spent
$sql = "SELECT SUM(total) as total_spent FROM commandeclient 
        WHERE id_client = :client_id AND statut = 'Livrée'";
$stmt = $conn->prepare($sql);
$stmt->execute([':client_id' => $_SESSION['client_id']]);
$total_spent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Tableau de Bord</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profil.php">
                                <i class="fas fa-user-cog"></i> Mon Profil</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-home"></i> Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="commandes.php">
                                <i class="fas fa-shopping-cart"></i> Mes Commandes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="produits.php">
                                <i class="fas fa-box"></i> Produits
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tableau de Bord</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
                    <div class="col">
                        <div class="card h-100 stat-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle mb-2 text-muted">Total Commandes</h6>
                                        <h2 class="card-title mb-0"><?php echo $total_orders; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 stat-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle mb-2 text-muted">Total Dépensé</h6>
                                        <h2 class="card-title mb-0"><?php echo number_format($total_spent, 0, ',', ' '); ?> FCFA</h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dépôts disponibles -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Dépôts disponibles</h5>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($depots as $depot): ?>
                                <div class="col">
                                    <div class="card h-100 stat-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($depot['nom']); ?></h5>
                                            <p class="card-text">
                                                <i class="fas fa-map-marker-alt text-primary"></i> 
                                                <?php echo htmlspecialchars($depot['adresse']); ?>
                                            </p>
                                            <p class="card-text">
                                                <i class="fas fa-phone text-primary"></i> 
                                                <?php echo htmlspecialchars($depot['contact']); ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <a href="produits.php?depot=<?php echo $depot['id']; ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-shopping-cart"></i> Commander
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Commandes récentes -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Commandes récentes</h5>
                        <a href="commandes.php" class="btn btn-sm btn-primary">
                            Voir toutes les commandes
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> Vous n'avez pas encore passé de commande
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>N° Commande</th>
                                            <th>Dépôt</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id_commande']; ?></td>
                                                <td><?php echo htmlspecialchars($order['depot_nom']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                                <td><?php echo number_format($order['total'], 0, ',', ' '); ?> FCFA</td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($order['statut']) {
                                                            'En attente' => 'warning',
                                                            'En préparation' => 'info',
                                                            'Livrée' => 'success',
                                                            'Annulée' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo $order['statut']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button onclick="showOrderDetails(<?php echo $order['id_commande']; ?>)" 
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> Détails
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Détails Commande -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la commande #<span id="orderNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <span id="orderDate"></span></p>
                            <p><strong>Statut:</strong> <span id="orderStatus"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Dépôt:</strong> <span id="orderDepot"></span></p>
                            <p><strong>Total:</strong> <span id="orderTotal"></span> FCFA</p>
                            </div>
                    </div>
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
                            <tbody id="orderDetails">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showOrderDetails(orderId) {
            fetch(`get_order_details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('orderNumber').textContent = data.order.id_commande;
                    document.getElementById('orderDate').textContent = new Date(data.order.date_commande).toLocaleDateString();
                    document.getElementById('orderStatus').textContent = data.order.statut;
                    document.getElementById('orderDepot').textContent = data.order.depot_nom;
                    document.getElementById('orderTotal').textContent = new Intl.NumberFormat().format(data.order.total);

                    const detailsHtml = data.details.map(item => `
                        <tr>
                            <td>${item.nom}</td>
                            <td>${item.quantite}</td>
                            <td>${new Intl.NumberFormat().format(item.prix_unit)} FCFA</td>
                            <td>${new Intl.NumberFormat().format(item.prix_unit * item.quantite)} FCFA</td>
                        </tr>
                    `).join('');
                    
                    document.getElementById('orderDetails').innerHTML = detailsHtml;
                    
                    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                });
        }
    </script>
</body>
</html>
