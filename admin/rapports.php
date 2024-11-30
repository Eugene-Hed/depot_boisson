<?php
require_once '../includes/database.php';
require_once '../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$depot_id = $_GET['depot'] ?? null;
if (!$depot_id) {
    header('Location: mes_depots.php');
    exit();
}

// Période du rapport
$debut = $_GET['debut'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-t');

// Statistiques des ventes
$sql = "SELECT 
        COUNT(*) as total_commandes,
        COALESCE(SUM(total), 0) as chiffre_affaires,
        COALESCE(AVG(total), 0) as panier_moyen
        FROM commandeclient 
        WHERE date_commande BETWEEN :debut AND :fin";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':debut' => $debut,
    ':fin' => $fin
]);
$stats_ventes = $stmt->fetch(PDO::FETCH_ASSOC);

// Produits les plus vendus
$sql = "SELECT p.nom, 
        COALESCE(SUM(dc.quantite), 0) as quantite_vendue, 
        COALESCE(SUM(dc.quantite * dc.prix_unit), 0) as total_ventes
        FROM produit p
        LEFT JOIN detailscommandeclient dc ON p.id_produit = dc.id_produit
        LEFT JOIN commandeclient cc ON dc.id_commande = cc.id_commande
        WHERE cc.date_commande BETWEEN :debut AND :fin
        GROUP BY p.id_produit, p.nom
        ORDER BY quantite_vendue DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':debut' => $debut,
    ':fin' => $fin
]);
$top_produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques des stocks
$sql = "SELECT 
        COUNT(*) as total_produits,
        COALESCE(SUM(quantite_stock), 0) as stock_total,
        COALESCE(AVG(prix_unit), 0) as prix_moyen
        FROM produit";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stats_stocks = $stmt->fetch(PDO::FETCH_ASSOC);

// Évolution des ventes
$sql = "SELECT date_commande, 
        COUNT(*) as nb_commandes, 
        COALESCE(SUM(total), 0) as total_ventes
        FROM commandeclient
        WHERE date_commande BETWEEN :debut AND :fin
        GROUP BY date_commande
        ORDER BY date_commande";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':debut' => $debut,
    ':fin' => $fin
]);
$evolution_ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports et Statistiques - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a class="navbar-brand" href="#">Rapports et Statistiques</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="tableau_de_bord.php?id=<?php echo $depot_id; ?>">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
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
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="tableau_de_bord.php?id=<?php echo $depot_id; ?>">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-chart-bar"></i> Rapports
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenu Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Filtres de période -->
                <div class="card mt-3 mb-4">
                    <div class="card-body">
                        <form class="row g-3" method="GET">
                            <input type="hidden" name="depot" value="<?php echo $depot_id; ?>">
                            <div class="col-auto">
                                <label for="debut" class="form-label">Date début</label>
                                <input type="date" class="form-control" id="debut" name="debut" 
                                       value="<?php echo $debut; ?>">
                            </div>
                            <div class="col-auto">
                                <label for="fin" class="form-label">Date fin</label>
                                <input type="date" class="form-control" id="fin" name="fin" 
                                       value="<?php echo $fin; ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Boutons d'export -->
                <div class="btn-group mb-3">
                    <a href="export_pdf.php?debut=<?php echo $debut; ?>&fin=<?php echo $fin; ?>" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Exporter en PDF
                    </a>
                    <a href="export_excel.php?debut=<?php echo $debut; ?>&fin=<?php echo $fin; ?>" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Exporter en Excel
                    </a>
                </div>

                <!-- Statistiques générales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Commandes</h5>
                                <p class="card-text h2"><?php echo number_format($stats_ventes['total_commandes']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Chiffre d'affaires</h5>
                                <p class="card-text h2"><?php echo number_format($stats_ventes['chiffre_affaires']); ?> FCFA</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Panier moyen</h5>
                                <p class="card-text h2"><?php echo number_format($stats_ventes['panier_moyen']); ?> FCFA</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Stock total</h5>
                                <p class="card-text h2"><?php echo number_format($stats_stocks['stock_total']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                Évolution des ventes
                            </div>
                            <div class="card-body">
                                <canvas id="evolutionVentes"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                Top 5 des produits vendus
                            </div>
                            <div class="card-body">
                                <canvas id="topProduits"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé -->
                <div class="card mb-4">
                    <div class="card-header">
                        Détails des ventes par produit
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité vendue</th>
                                        <th>Total des ventes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_produits as $produit): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                        <td><?php echo number_format($produit['quantite_vendue']); ?></td>
                                        <td><?php echo number_format($produit['total_ventes']); ?> FCFA</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique évolution des ventes
        const ctxEvolution = document.getElementById('evolutionVentes').getContext('2d');
        new Chart(ctxEvolution, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($evolution_ventes, 'date_commande')); ?>,
                datasets: [{
                    label: 'Ventes journalières',
                    data: <?php echo json_encode(array_column($evolution_ventes, 'total_ventes')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique top produits
        const ctxTop = document.getElementById('topProduits').getContext('2d');
        new Chart(ctxTop, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($top_produits, 'nom')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($top_produits, 'quantite_vendue')); ?>,
                    backgroundColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)', 
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
