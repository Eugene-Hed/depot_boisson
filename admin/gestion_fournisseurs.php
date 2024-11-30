
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

// Récupération des fournisseurs avec leurs statistiques
$sql = "SELECT f.*, 
        COUNT(DISTINCT cf.id_commande) as total_commandes,
        SUM(cf.total) as montant_total 
        FROM fournisseur f
        LEFT JOIN commandefournisseur cf ON f.id_fournisseur = cf.id_fournisseur
        GROUP BY f.id_fournisseur
        ORDER BY f.nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajout d'un fournisseur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);

    $sql = "INSERT INTO fournisseur (nom, email, telephone, adresse) 
            VALUES (:nom, :email, :telephone, :adresse)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone,
        ':adresse' => $adresse
    ]);

    header("Location: gestion_fournisseurs.php?depot=$depot_id&success=1");
    exit();
}

// Modification d'un fournisseur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_fournisseur = $_POST['id_fournisseur'];
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);

    $sql = "UPDATE fournisseur SET 
            nom = :nom,
            email = :email,
            telephone = :telephone,
            adresse = :adresse
            WHERE id_fournisseur = :id_fournisseur";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone,
        ':adresse' => $adresse,
        ':id_fournisseur' => $id_fournisseur
    ]);

    header("Location: gestion_fournisseurs.php?depot=$depot_id&success=2");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Fournisseurs - Administration</title>
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
            <a class="navbar-brand" href="#">Gestion des Fournisseurs</a>
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
                                <i class="fas fa-truck"></i> Fournisseurs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Fournisseurs</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFournisseurModal">
                        <i class="fas fa-plus"></i> Nouveau Fournisseur
                    </button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        if ($_GET['success'] == 1) echo "Fournisseur ajouté avec succès.";
                        if ($_GET['success'] == 2) echo "Fournisseur modifié avec succès.";
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Liste des fournisseurs -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="fournisseursTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Total Commandes</th>
                                    <th>Montant Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fournisseurs as $fournisseur): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fournisseur['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($fournisseur['email']); ?></td>
                                        <td><?php echo htmlspecialchars($fournisseur['telephone']); ?></td>
                                        <td><?php echo $fournisseur['total_commandes']; ?></td>
                                        <td><?php echo number_format($fournisseur['montant_total'] ?? 0, 0, ',', ' '); ?> FCFA</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editFournisseur(<?php echo $fournisseur['id_fournisseur']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info" onclick="viewFournisseurDetails(<?php echo $fournisseur['id_fournisseur']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
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

    <!-- Modal Ajout Fournisseur -->
    <div class="modal fade" id="addFournisseurModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Fournisseur</h5>
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
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone">
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
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

    <!-- Modal Modification Fournisseur -->
    <div class="modal fade" id="editFournisseurModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Fournisseur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_fournisseur" id="edit_id_fournisseur">
                        
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>

                        <div class="mb-3">
                            <label for="edit_telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="edit_telephone" name="telephone">
                        </div>

                        <div class="mb-3">
                            <label for="edit_adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="edit_adresse" name="adresse" rows="3"></textarea>
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
            $('#fournisseursTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                }
            });
        });

        function editFournisseur(fournisseurId) {
            $.get('get_fournisseur.php', {id: fournisseurId}, function(fournisseur) {
                $('#edit_id_fournisseur').val(fournisseur.id_fournisseur);
                $('#edit_nom').val(fournisseur.nom);
                $('#edit_email').val(fournisseur.email);
                $('#edit_telephone').val(fournisseur.telephone);
                $('#edit_adresse').val(fournisseur.adresse);
                
                $('#editFournisseurModal').modal('show');
            }, 'json');
        }

        function viewFournisseurDetails(fournisseurId) {
            $.get('get_fournisseur_details.php', {id: fournisseurId}, function(details) {
                let content = `
                    <div class="modal fade" id="detailsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Détails du Fournisseur</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <h6>Historique des Commandes</h6>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                
                details.commandes.forEach(commande => {
                    content += `
                        <tr>
                            <td>${commande.date_commande}</td>
                            <td>${commande.total} FCFA</td>
                            <td><span class="badge bg-${commande.statut === 'Reçue' ? 'success' : 'warning'}">${commande.statut}</span></td>
                        </tr>`;
                });
                
                content += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>`;
                
                $(content).modal('show');
            }, 'json');
        }
    </script>
</body>
</html>