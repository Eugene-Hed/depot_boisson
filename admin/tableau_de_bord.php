<?php
require_once '../includes/database.php';
session_write_close();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$depot_id = $_GET['id'] ?? null;
if (!$depot_id) {
    header('Location: mes_depots.php');
    exit();
}

// Fetch depot info
$sql = "SELECT * FROM depots WHERE id = :id AND id_proprietaire = :id_proprietaire";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $depot_id,
    ':id_proprietaire' => $_SESSION['user_id']
]);
$depot = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if depot doesn't exist or doesn't belong to user
if (!$depot) {
    header('Location: ../mes_depots.php');
    exit();
}

// Statistics
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM produit WHERE id_depot = " . $depot_id . ") as total_produits,
    (SELECT COUNT(*) FROM commandeclient WHERE id_depot = " . $depot_id . ") as total_commandes,
    (SELECT COUNT(*) FROM client) as total_clients,
    (SELECT COALESCE(SUM(total), 0) 
     FROM commandeclient 
     WHERE id_depot = " . $depot_id . "
     AND MONTH(date_commande) = MONTH(CURRENT_DATE)
     AND YEAR(date_commande) = YEAR(CURRENT_DATE)
     AND statut = 'Livrée') as revenu_mensuel";
$stmt = $conn->prepare($sql_stats);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Recent Activities
$sql = "SELECT cc.*, c.nom as client_nom 
        FROM commandeclient cc 
        JOIN client c ON cc.id_client = c.id_client 
        WHERE cc.id_depot = :depot_id 
        ORDER BY cc.date_commande DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute([':depot_id' => $depot_id]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low Stock Products
$sql = "SELECT nom, quantite_stock 
        FROM produit 
        WHERE id_depot = :depot_id 
        AND quantite_stock <= 5 
        ORDER BY quantite_stock ASC LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->execute([':depot_id' => $depot_id]);
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo htmlspecialchars($depot['nom']); ?></title>
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
            <a class="navbar-brand" href="#">
                <?php echo htmlspecialchars($depot['nom']); ?>
            </a>
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
                                <i class="fas fa-user-cog"></i> Profil</a>
                            </li>
                            <li><a class="dropdown-item" href="../mes_depots.php">
                                <i class="fas fa-warehouse"></i> Mes Dépôts</a>
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
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_produits.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-box"></i> Produits
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="commandes_clients.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-shopping-cart"></i> Commandes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_clients.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-users"></i> Clients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_fournisseurs.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-truck"></i> Fournisseurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_categories.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-tags"></i> Catégories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="rapports.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-chart-bar"></i> Rapports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="parametres.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tableau de Bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
                    <div class="col">
                        <div class="card h-100 stat-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle mb-2 text-muted">Produits</h6>
                                        <h2 class="card-title mb-0"><?php echo number_format($stats['total_produits']); ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-box fa-2x"></i>
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
                                        <h6 class="card-subtitle mb-2 text-muted">Commandes</h6>
                                        <h2 class="card-title mb-0"><?php echo number_format($stats['total_commandes']); ?></h2>
                                    </div>
                                    <div class="text-success">
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
                                        <h6 class="card-subtitle mb-2 text-muted">Clients</h6>
                                        <h2 class="card-title mb-0"><?php echo number_format($stats['total_clients']); ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-users fa-2x"></i>
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
                                        <h6 class="card-subtitle mb-2 text-muted">Revenu Mensuel</h6>
                                        <h2 class="card-title mb-0"><?php echo number_format($stats['revenu_mensuel']); ?> FCFA</h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities and Low Stock -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Activités Récentes</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                <?php foreach($recent_activities as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">Commande #<?php echo $activity['id_commande']; ?></h6>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($activity['date_commande'])); ?></small>
                                            </div>
                                            <p class="mb-1">Client: <?php echo htmlspecialchars($activity['client_nom']); ?></p>
                                            <small class="text-muted">Montant: <?php echo number_format($activity['total']); ?> FCFA</small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Produits en Rupture</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach($low_stock as $product): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['nom']); ?></h6>
                                                <span class="badge <?php echo $product['quantite_stock'] == 0 ? 'bg-danger' : 'bg-warning'; ?>">
                                                    <?php echo $product['quantite_stock']; ?> en stock
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="stock_alert.php?depot=<?php echo $depot_id; ?>" class="btn btn-outline-danger btn-sm w-100">
                                    Voir tous les produits en alerte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>

