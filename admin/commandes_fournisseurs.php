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

// Récupération des produits en alerte
$sql = "SELECT p.*, c.nom as categorie_nom 
        FROM produit p 
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
        WHERE p.quantite_stock <= 100 
        ORDER BY p.quantite_stock ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$produits_alerte = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des fournisseurs
$sql = "SELECT * FROM fournisseur ORDER BY nom";
$stmt = $conn->prepare($sql);
$stmt->execute();
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des commandes fournisseurs
$sql = "SELECT cf.*, f.nom as fournisseur_nom 
        FROM commandefournisseur cf
        JOIN fournisseur f ON cf.id_fournisseur = f.id_fournisseur
        WHERE cf.id_depot = :depot_id
        ORDER BY cf.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':depot_id' => $depot_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la nouvelle commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'commander') {
    try {
        $conn->beginTransaction();
        
        $fournisseur_id = $_POST['fournisseur'];
        $total = 0;
        
        // Création de la commande
        $sql = "INSERT INTO commandefournisseur (id_fournisseur, date_commande, total, statut, id_depot) 
                VALUES (:fournisseur_id, CURRENT_DATE, :total, 'En attente', :depot_id)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fournisseur_id' => $fournisseur_id,
            ':total' => $total,
            ':depot_id' => $depot_id
        ]);
        
        $commande_id = $conn->lastInsertId();
        
        // Ajout des détails de la commande
        foreach ($_POST['produits'] as $produit_id => $quantite) {
            if ($quantite > 0) {
                $prix = $_POST['prix'][$produit_id];
                $sous_total = $quantite * $prix;
                $total += $sous_total;
                
                $sql = "INSERT INTO detailscommandefournisseur (id_commande, id_produit, quantite, prix_unit) 
                        VALUES (:commande_id, :produit_id, :quantite, :prix)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':commande_id' => $commande_id,
                    ':produit_id' => $produit_id,
                    ':quantite' => $quantite,
                    ':prix' => $prix
                ]);
            }
        }
        
        // Mise à jour du total de la commande
        $sql = "UPDATE commandefournisseur SET total = :total WHERE id_commande = :commande_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':total' => $total, ':commande_id' => $commande_id]);
        
        $conn->commit();
        header("Location: commandes_fournisseurs.php?depot=$depot_id&success=1");
        exit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        $error = "Erreur lors de la création de la commande";
    }
}

// Traitement de la validation de réception
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    try {
        $conn->beginTransaction();
        
        $commande_id = $_POST['commande_id'];
        
        // Récupération des détails de la commande
        $sql = "SELECT * FROM detailscommandefournisseur WHERE id_commande = :commande_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':commande_id' => $commande_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mise à jour des quantités de produits
        foreach ($details as $detail) {
            $sql = "UPDATE produit 
                    SET quantite_stock = quantite_stock + :quantite 
                    WHERE id_produit = :produit_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':quantite' => $detail['quantite'],
                ':produit_id' => $detail['id_produit']
            ]);
        }
        
        // Mise à jour du statut de la commande
        $sql = "UPDATE commandefournisseur SET statut = 'Reçue' WHERE id_commande = :commande_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':commande_id' => $commande_id]);
        
        $conn->commit();
        header("Location: commandes_fournisseurs.php?depot=$depot_id&success=2");
        exit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        $error = "Erreur lors de la validation de la commande";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes Fournisseurs - Administration</title>
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
        .alert-stock {
            background-color: #fff3cd;
            border-color: #ffecb5;
            color: #664d03;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Commandes Fournisseurs</a>
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
                            <a class="nav-link" data-bs-toggle="collapse" href="#commandesSubmenu">
                                <i class="fas fa-shopping-cart"></i> Commandes
                            </a>
                            <div class="collapse show" id="commandesSubmenu">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link" href="commandes_clients.php?depot=<?php echo $depot_id; ?>">
                                            <i class="fas fa-users"></i> Commandes Clients
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#">
                                            <i class="fas fa-truck"></i> Commandes Fournisseurs
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenu Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Alerte de stock -->
                <?php if (!empty($produits_alerte)): ?>
                    <div class="alert alert-stock mt-3">
                        <h4><i class="fas fa-exclamation-triangle"></i> Alerte de stock</h4>
                        <p>Les produits suivants nécessitent un réapprovisionnement :</p>
                        <ul>
                            <?php foreach ($produits_alerte as $produit): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($produit['nom']); ?></strong> - 
                                    Stock actuel: <span class="badge bg-danger"><?php echo $produit['quantite_stock']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                            <i class="fas fa-plus"></i> Créer une commande de réapprovisionnement
                        </button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Commandes Fournisseurs</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                        <i class="fas fa-plus"></i> Nouvelle Commande
                    </button>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php if ($_GET['success'] == 1): ?>
                            La commande a été créée avec succès.
                        <?php elseif ($_GET['success'] == 2): ?>
                            La réception de la commande a été validée avec succès.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Liste des commandes -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="commandesTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Fournisseur</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo htmlspecialchars($commande['fournisseur_nom']); ?></td>
                                        <td><?php echo number_format($commande['total'], 0, ',', ' '); ?> FCFA</td>
                                        <td>
                                            <span class="badge <?php echo $commande['statut'] === 'Reçue' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $commande['statut']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($commande['statut'] === 'En attente'): ?>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="valider">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirmer la réception de cette commande ?')">
                                                        <i class="fas fa-check"></i> Valider Réception
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-info btn-sm" onclick="viewDetails(<?php echo $commande['id_commande']; ?>)">
                                                <i class="fas fa-eye"></i> Détails
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

    <!-- Modal Nouvelle Commande -->
    <div class="modal fade" id="newOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Commande Fournisseur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="commander">
                        
                        <div class="mb-3">
                            <label class="form-label">Fournisseur</label>
                            <select name="fournisseur" class="form-select" required>
                                <?php foreach ($fournisseurs as $fournisseur): ?>
                                    <option value="<?php echo $fournisseur['id_fournisseur']; ?>">
                                        <?php echo htmlspecialchars($fournisseur['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Stock Actuel</th>
                                    <th>Prix Unitaire</th>
                                    <th>Quantité à Commander</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produits_alerte as $produit): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?php echo $produit['quantite_stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" name="prix[<?php echo $produit['id_produit']; ?>]" 
                                                   class="form-control" value="<?php echo $produit['prix_unit']; ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="produits[<?php echo $produit['id_produit']; ?>]" 
                                                   class="form-control" value="100" min="0" required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer la Commande</button>
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
            $('#commandesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                },
                order: [[0, 'desc']]
            });
        });

        function viewDetails(commandeId) {
            $.get('get_commande_details.php', {id: commandeId}, function(details) {
                let content = `
                    <div class="modal fade" id="detailsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Détails de la Commande #${commandeId}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Quantité</th>
                                                <th>Prix Unitaire</th>
                                                <th>Sous-total</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                
                details.forEach(detail => {
                    content += `
                        <tr>
                            <td>${detail.nom_produit}</td>
                            <td>${detail.quantite}</td>
                            <td>${detail.prix_unit} FCFA</td>
                            <td>${detail.quantite * detail.prix_unit} FCFA</td>
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
