
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

// Récupération des commandes clients
$sql = "SELECT cc.*, c.nom as client_nom 
        FROM commandeclient cc
        JOIN client c ON cc.id_client = c.id_client
        WHERE cc.id_depot = :depot_id
        ORDER BY cc.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':depot_id' => $depot_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validation de la commande (passage à "En préparation")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    $commande_id = $_POST['commande_id'];
    
    $sql = "UPDATE commandeclient SET statut = 'En préparation' WHERE id_commande = :commande_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':commande_id' => $commande_id]);
    
    header("Location: commandes_clients.php?depot=$depot_id&success=1");
    exit();
}

// Validation de la livraison
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'livrer') {
    try {
        $conn->beginTransaction();
        
        $commande_id = $_POST['commande_id'];
        
        // Récupération des détails de la commande
        $sql = "SELECT * FROM detailscommandeclient WHERE id_commande = :commande_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':commande_id' => $commande_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mise à jour des stocks
        foreach ($details as $detail) {
            $sql = "UPDATE produit 
                    SET quantite_stock = quantite_stock - :quantite 
                    WHERE id_produit = :produit_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':quantite' => $detail['quantite'],
                ':produit_id' => $detail['id_produit']
            ]);
        }
        
        // Mise à jour du statut de la commande
        $sql = "UPDATE commandeclient SET statut = 'Livrée' WHERE id_commande = :commande_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':commande_id' => $commande_id]);
        
        // Création de l'entrée dans la table livraisonclient
        $sql = "INSERT INTO livraisonclient (id_commande, date_livraison, statut) 
                VALUES (:commande_id, CURRENT_DATE, 'Livrée')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':commande_id' => $commande_id]);
        
        $conn->commit();
        header("Location: commandes_clients.php?depot=$depot_id&success=2");
        exit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        $error = "Erreur lors de la validation de la livraison";
    }
}

// Annulation de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler') {
    $commande_id = $_POST['commande_id'];
    
    $sql = "UPDATE commandeclient SET statut = 'Annulée' WHERE id_commande = :commande_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':commande_id' => $commande_id]);
    
    header("Location: commandes_clients.php?depot=$depot_id&success=3");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes Clients - Administration</title>
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
            <a class="navbar-brand" href="#">Commandes Clients</a>
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
                                        <a class="nav-link active" href="#">
                                            <i class="fas fa-users"></i> Commandes Clients
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="commandes_fournisseurs.php?depot=<?php echo $depot_id; ?>">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Commandes Clients</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        switch($_GET['success']) {
                            case 1:
                                echo "La commande a été validée et mise en préparation.";
                                break;
                            case 2:
                                echo "La livraison a été validée avec succès.";
                                break;
                            case 3:
                                echo "La commande a été annulée.";
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Liste des commandes -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="commandesTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo htmlspecialchars($commande['client_nom']); ?></td>
                                        <td><?php echo number_format($commande['total'], 0, ',', ' '); ?> FCFA</td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($commande['statut']) {
                                                    case 'En attente':
                                                        echo 'bg-warning';
                                                        break;
                                                    case 'En préparation':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'Livrée':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'Annulée':
                                                        echo 'bg-danger';
                                                        break;
                                                }
                                            ?>">
                                                <?php echo $commande['statut']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($commande['statut'] === 'En attente'): ?>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="valider">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Valider cette commande ?')">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($commande['statut'] === 'En préparation'): ?>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="livrer">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Confirmer la livraison ?')">
                                                        <i class="fas fa-truck"></i> Valider Livraison
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($commande['statut'] === 'En attente' || $commande['statut'] === 'En préparation'): ?>
                                                <form action="" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Annuler cette commande ?')">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                    </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-primary btn-sm" onclick="viewDetails(<?php echo $commande['id_commande']; ?>)">
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
            $.get('get_commande_client_details.php', {id: commandeId}, function(details) {
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