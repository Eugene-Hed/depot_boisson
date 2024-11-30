
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

// Récupération des catégories avec le nombre de produits
$sql = "SELECT c.*, COUNT(p.id_produit) as nombre_produits 
        FROM categorie c
        LEFT JOIN produit p ON c.id_categorie = p.id_categorie
        GROUP BY c.id_categorie
        ORDER BY c.nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajout d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = htmlspecialchars($_POST['nom']);
    $description = htmlspecialchars($_POST['description']);

    $sql = "INSERT INTO categorie (nom, description) VALUES (:nom, :description)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':description' => $description
    ]);

    header("Location: gestion_categories.php?depot=$depot_id&success=1");
    exit();
}

// Modification d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_categorie = $_POST['id_categorie'];
    $nom = htmlspecialchars($_POST['nom']);
    $description = htmlspecialchars($_POST['description']);

    $sql = "UPDATE categorie SET nom = :nom, description = :description WHERE id_categorie = :id_categorie";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':description' => $description,
        ':id_categorie' => $id_categorie
    ]);

    header("Location: gestion_categories.php?depot=$depot_id&success=2");
    exit();
}

// Suppression d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id_categorie = $_POST['id_categorie'];

    // Vérifier si la catégorie contient des produits
    $sql = "SELECT COUNT(*) FROM produit WHERE id_categorie = :id_categorie";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_categorie' => $id_categorie]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        header("Location: gestion_categories.php?depot=$depot_id&error=1");
        exit();
    }

    $sql = "DELETE FROM categorie WHERE id_categorie = :id_categorie";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_categorie' => $id_categorie]);

    header("Location: gestion_categories.php?depot=$depot_id&success=3");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - Administration</title>
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
            <a class="navbar-brand" href="#">Gestion des Catégories</a>
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
                            <a class="nav-link" href="gestion_produits.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-box"></i> Produits
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-tags"></i> Catégories
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Catégories</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategorieModal">
                        <i class="fas fa-plus"></i> Nouvelle Catégorie
                    </button>
                </div>

                <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        Impossible de supprimer cette catégorie car elle contient des produits.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        switch($_GET['success']) {
                            case 1:
                                echo "Catégorie ajoutée avec succès.";
                                break;
                            case 2:
                                echo "Catégorie modifiée avec succès.";
                                break;
                            case 3:
                                echo "Catégorie supprimée avec succès.";
                                break;
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Liste des catégories -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Nombre de Produits</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $categorie): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($categorie['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($categorie['description']); ?></td>
                                        <td><?php echo $categorie['nombre_produits']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editCategorie(<?php echo $categorie['id_categorie']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($categorie['nombre_produits'] == 0): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteCategorie(<?php echo $categorie['id_categorie']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Ajout Catégorie -->
    <div class="modal fade" id="addCategorieModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
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

    <!-- Modal Modification Catégorie -->
    <div class="modal fade" id="editCategorieModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_categorie" id="edit_id_categorie">
                        
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#categoriesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                }
            });
        });

        function editCategorie(categorieId) {
            $.get('get_categorie.php', {id: categorieId}, function(categorie) {
                $('#edit_id_categorie').val(categorie.id_categorie);
                $('#edit_nom').val(categorie.nom);
                $('#edit_description').val(categorie.description);
                
                $('#editCategorieModal').modal('show');
            }, 'json');
        }

        function deleteCategorie(categorieId) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id_categorie" value="${categorieId}">
                `;
                document.body.append(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

