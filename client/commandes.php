<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../index.php');
    exit();
}

// Fetch client orders with depot info
$sql = "SELECT cc.*, d.nom as depot_nom 
        FROM commandeclient cc 
        JOIN depots d ON cc.id_depot = d.id 
        WHERE cc.id_client = :client_id 
        ORDER BY cc.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':client_id' => $_SESSION['client_id']]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - Dépôt Manager</title>
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Mes Commandes</a>
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
                            <li><a class="dropdown-item" href="profil.php">
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
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="accueil.php">
                                <i class="fas fa-home"></i> Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
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
                    <h1 class="h2">Mes Commandes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>

                <?php if (empty($commandes)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Vous n'avez pas encore passé de commande.
                        <a href="produits.php" class="alert-link">Découvrez nos produits</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Dépôt</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td>#<?php echo $commande['id_commande']; ?></td>
                                        <td><?php echo htmlspecialchars($commande['depot_nom']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo number_format($commande['total'], 0, ',', ' '); ?> FCFA</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($commande['statut']) {
                                                    'En attente' => 'warning',
                                                    'En préparation' => 'info',
                                                    'Livrée' => 'success',
                                                    'Annulée' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo $commande['statut']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="showOrderDetails(<?php echo $commande['id_commande']; ?>)" 
                                                    class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Détails
                                            </button>
                                            <?php if ($commande['statut'] === 'En attente'): ?>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="annulerCommande(<?php echo $commande['id_commande']; ?>)">
                                                    <i class="fas fa-times"></i> Annuler
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

        function annulerCommande(orderId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                window.location.href = `annuler_commande.php?id=${orderId}`;
            }
        }
    </script>
</body>
</html>
