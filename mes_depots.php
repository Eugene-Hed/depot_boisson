<?php
require_once 'includes/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM produit p WHERE p.id_depot = d.id) as total_produits,
        (SELECT COUNT(*) FROM commandeclient cc WHERE cc.id_depot = d.id) as total_commandes 
        FROM depots d 
        WHERE d.id_proprietaire = :id_proprietaire";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_proprietaire' => $_SESSION['user_id']]);
$depots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Dépôts - Gestion de Dépôt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .depot-card {
            transition: transform 0.2s;
        }
        .depot-card:hover {
            transform: translateY(-5px);
        }
        .stats-badge {
            font-size: 0.9rem;
            padding: 0.5rem;
            margin-right: 0.5rem;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Dépôt Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Mes Dépôts</h1>
            <a href="register_depot.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau Dépôt
            </a>
        </div>

        <?php if (empty($depots)): ?>
            <div class="text-center py-5">
                <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                <h3>Aucun dépôt enregistré</h3>
                <p class="text-muted">Commencez par créer votre premier dépôt</p>
                <a href="register_depot.php" class="btn btn-primary">
                    Créer un dépôt
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($depots as $depot): ?>
                    <div class="col">
                        <div class="card h-100 depot-card shadow-sm">
                            <?php if ($depot['logo'] && file_exists($depot['logo'])): ?>
                                <img src="<?php echo htmlspecialchars($depot['logo']); ?>" class="card-img-top" alt="Logo du dépôt">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-warehouse fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
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
                                
                                <div class="d-flex mb-3">
                                    <span class="stats-badge bg-info text-white">
                                       <i class="fas fa-box"></i> 
                                          <?php echo isset($depot['total_produits']) ? $depot['total_produits'] : 0; ?> produits
                                    </span>
                                    <span class="stats-badge bg-success text-white">
                                        <i class="fas fa-check"></i> 
                                         <?php echo isset($depot['total_commandes']) ? $depot['total_commandes'] : 0; ?> commandes
                                    </span>
                                </div>

                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="btn-group w-100">
                                    <a href="admin/tableau_de_bord.php?id=<?php echo $depot['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-cog"></i> Gérer
                                    </a>
                                    <a href="stock_depot.php?id=<?php echo $depot['id']; ?>" class="btn btn-outline-success">
                                        <i class="fas fa-boxes"></i> Stock
                                    </a>
                                    <a href="edit_depot.php?id=<?php echo $depot['id']; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
