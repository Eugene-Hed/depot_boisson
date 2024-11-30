<?php
require_once '../includes/database.php';
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

// Récupération des catégories
$sql = "SELECT * FROM categorie ORDER BY nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des produits avec leurs catégories
$sql = "SELECT p.*, c.nom as categorie_nom 
        FROM produit p 
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
        ORDER BY p.nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'ajout d'un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = htmlspecialchars($_POST['nom']);
    $categorie = $_POST['categorie'];
    $volume = $_POST['volume'];
    $prix = $_POST['prix'];
    $quantite = $_POST['quantite'];
    $description = htmlspecialchars($_POST['description']);

    $sql = "INSERT INTO produit (id_categorie, nom, volume, prix_unit, quantite_stock, description) 
            VALUES (:categorie, :nom, :volume, :prix, :quantite, :description)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':categorie' => $categorie,
        ':nom' => $nom,
        ':volume' => $volume,
        ':prix' => $prix,
        ':quantite' => $quantite,
        ':description' => $description
    ]);

    header("Location: gestion_produits.php?depot=$depot_id&success=1");
    exit();
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_produit = $_POST['id_produit'];
    $nom = htmlspecialchars($_POST['nom']);
    $categorie = $_POST['categorie'];
    $volume = $_POST['volume'];
    $prix = $_POST['prix'];
    $quantite = $_POST['quantite'];
    $description = htmlspecialchars($_POST['description']);

    $sql = "UPDATE produit SET 
            id_categorie = :categorie,
            nom = :nom,
            volume = :volume,
            prix_unit = :prix,
            quantite_stock = :quantite,
            description = :description
            WHERE id_produit = :id_produit";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':categorie' => $categorie,
        ':nom' => $nom,
        ':volume' => $volume,
        ':prix' => $prix,
        ':quantite' => $quantite,
        ':description' => $description,
        ':id_produit' => $id_produit
    ]);

    header("Location: gestion_produits.php?depot=$depot_id&success=2");
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id_produit = $_POST['id_produit'];
    
    $sql = "DELETE FROM produit WHERE id_produit = :id_produit";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_produit' => $id_produit]);

    header("Location: gestion_produits.php?depot=$depot_id&success=3");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
            <a class="navbar-brand" href="#">Administration des Produits</a>
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
                            <li><a class="dropdown-item" href="tableau_de_bord.php?id=<?php echo $depot_id; ?>">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
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
                            <a class="nav-link" href="tableau_de_bord.php?id=<?php echo $depot_id; ?>">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-box"></i> Produits
                            </a>
                        </li>
                        <!-- Autres liens du menu -->
                    </ul>
                </div>
            </nav>

            <!-- Contenu Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Produits</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Nouveau Produit
                    </button>
                </div>

                <!-- Tableau des produits -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Volume</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td><?php echo $produit['id_produit']; ?></td>
                                    <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($produit['categorie_nom']); ?></td>
                                    <td><?php echo $produit['volume']; ?>L</td>
                                    <td><?php echo number_format($produit['prix_unit']); ?> FCFA</td>
                                    <td>
                                        <span class="badge <?php echo $produit['quantite_stock'] <= 100 ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $produit['quantite_stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo $produit['id_produit']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $produit['id_produit']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Ajout Produit -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom du produit</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie" required>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id_categorie']; ?>">
                                        <?php echo htmlspecialchars($categorie['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="volume" class="form-label">Volume</label>
                                <input type="number" class="form-control" id="volume" name="volume" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prix" class="form-label">Prix unitaire</label>
                                <input type="number" class="form-control" id="prix" name="prix" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quantite" class="form-label">Quantité en stock</label>
                            <input type="number" class="form-control" id="quantite" name="quantite" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification Produit -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="editForm">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id_produit" id="edit_id_produit">
                    
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom du produit</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                                </div>
                                <div class="mb-3">
                        <label for="edit_categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="edit_categorie" name="categorie" required>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id_categorie']; ?>">
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_volume" class="form-label">Volume</label>
                            <input type="number" class="form-control" id="edit_volume" name="volume" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_prix" class="form-label">Prix unitaire</label>
                            <input type="number" class="form-control" id="edit_prix" name="prix" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_quantite" class="form-label">Quantité en stock</label>
                        <input type="number" class="form-control" id="edit_quantite" name="quantite" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                }
            });
        });

        function editProduct(id) {
    $.get('get_product.php', {id: id}, function(produit) {
        $('#edit_id_produit').val(produit.id_produit);
        $('#edit_nom').val(produit.nom);
        $('#edit_categorie').val(produit.id_categorie);
        $('#edit_volume').val(produit.volume);
        $('#edit_prix').val(produit.prix_unit);
        $('#edit_quantite').val(produit.quantite_stock);
        $('#edit_description').val(produit.description);
        
        var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
    });
}


        function deleteProduct(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="id_produit" value="${id}">
        `;
        document.body.append(form);
        form.submit();
    }
        }
    </script>
</body>
</html>
