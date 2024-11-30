<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../index.php');
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle depot selection
$depot_id = $_GET['depot'] ?? null;
if (!$depot_id) {
    header('Location: accueil.php');
    exit();
}

// Fetch depot info
$sql = "SELECT * FROM depots WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $depot_id]);
$depot = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    $sql = "SELECT * FROM produit WHERE id_produit = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $quantity <= $product['quantite_stock']) {
        $_SESSION['cart'][$depot_id][$product_id] = [
            'nom' => $product['nom'],
            'prix' => $product['prix_unit'],
            'quantite' => $quantity
        ];
    }
}

// Remove from cart
if (isset($_GET['remove_from_cart'])) {
    $product_id = $_GET['remove_from_cart'];
    unset($_SESSION['cart'][$depot_id][$product_id]);
}

// Calculate cart total
$cart_total = 0;
$cart_items = 0;
if (isset($_SESSION['cart'][$depot_id])) {
    foreach ($_SESSION['cart'][$depot_id] as $item) {
        $cart_total += $item['prix'] * $item['quantite'];
        $cart_items += $item['quantite'];
    }
}

// Fetch categories for filter
$sql = "SELECT * FROM categorie ORDER BY nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query based on filters
$where_clause = "WHERE id_depot = :depot_id";
$params = [':depot_id' => $depot_id];

if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
    $where_clause .= " AND p.id_categorie = :categorie";
    $params[':categorie'] = $_GET['categorie'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_clause .= " AND p.nom LIKE :search";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

// Fetch products with their categories
$sql = "SELECT p.*, c.nom as categorie_nom 
        FROM produit p 
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
        $where_clause
        ORDER BY p.nom";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - <?php echo htmlspecialchars($depot['nom']); ?></title>
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
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($depot['nom']); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex ms-auto me-3">
                    <input type="hidden" name="depot" value="<?php echo $depot_id; ?>">
                    <input class="form-control me-2" type="search" name="search" 
                           placeholder="Rechercher un produit..." 
                           value="<?php echo $_GET['search'] ?? ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <ul class="navbar-nav">
                    <li class="nav-item me-3">
                        <a href="#" class="btn btn-light position-relative" 
                           data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                            <i class="fas fa-shopping-cart"></i> Panier
                            <?php if ($cart_items > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_items; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
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

    <!-- Shopping Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Panier - <?php echo htmlspecialchars($depot['nom']); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <?php if (empty($_SESSION['cart'][$depot_id])): ?>
                <p class="text-muted">Votre panier est vide</p>
            <?php else: ?>
                <div class="list-group mb-3">
                    <?php foreach ($_SESSION['cart'][$depot_id] as $product_id => $item): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['nom']); ?></h6>
                                <a href="?depot=<?php echo $depot_id; ?>&remove_from_cart=<?php echo $product_id; ?>" 
                                   class="text-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                            <p class="mb-1">
                                <?php echo $item['quantite']; ?> x 
                                <?php echo number_format($item['prix'], 0, ',', ' '); ?> FCFA
                            </p>
                            <small class="text-muted">
                                Total: <?php echo number_format($item['prix'] * $item['quantite'], 0, ',', ' '); ?> FCFA
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <h5>Total</h5>
                    <h5><?php echo number_format($cart_total, 0, ',', ' '); ?> FCFA</h5>
                </div>
                <a href="commander.php?depot=<?php echo $depot_id; ?>" class="btn btn-success w-100">
                    <i class="fas fa-check"></i> Passer la commande
                </a>
            <?php endif; ?>
        </div>
    </div>

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
                            <a class="nav-link" href="commandes.php">
                                <i class="fas fa-shopping-cart"></i> Mes Commandes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-box"></i> Produits
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Catégories</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link <?php echo !isset($_GET['categorie']) ? 'active' : ''; ?>" 
                               href="?depot=<?php echo $depot_id; ?>">
                                Toutes les catégories
                            </a>
                        </li>
                        <?php foreach ($categories as $categorie): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == $categorie['id_categorie']) ? 'active' : ''; ?>" 
                                   href="?depot=<?php echo $depot_id; ?>&categorie=<?php echo $categorie['id_categorie']; ?>">
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Produits disponibles</h1>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                    <?php foreach ($produits as $produit): ?>
                        <div class="col">
                            <div class="card h-100 product-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($produit['nom']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($produit['categorie_nom']); ?>
                                        </small>
                                    </p>
                                    <p class="card-text">
                                        Volume: <?php echo $produit['volume']; ?>L
                                    </p>
                                    <p class="card-text">
                                        <strong class="text-primary">
                                            <?php echo number_format($produit['prix_unit'], 0, ',', ' '); ?> FCFA
                                        </strong>
                                    </p>
                                    <p class="card-text">
                                    <span class="badge <?php echo $produit['quantite_stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $produit['quantite_stock'] > 0 ? 'En stock' : 'Rupture de stock'; ?>
                                            <?php if ($produit['quantite_stock'] > 0): ?>
                                                (<?php echo $produit['quantite_stock']; ?>)
                                            <?php endif; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <?php if ($produit['quantite_stock'] > 0): ?>
                                        <form action="" method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="product_id" value="<?php echo $produit['id_produit']; ?>">
                                            <input type="number" name="quantity" value="1" min="1" 
                                                   max="<?php echo $produit['quantite_stock']; ?>" 
                                                   class="form-control" style="width: 80px;">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary flex-grow-1">
                                                <i class="fas fa-cart-plus"></i> Ajouter
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-times"></i> Indisponible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
