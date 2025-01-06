<?php
$current_page = 'commandes_clients';
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

// Récupération des commandes clients
$sql = "SELECT cc.*, c.nom as client_nom 
        FROM commandeclient cc
        JOIN client c ON cc.id_client = c.id_client
        WHERE cc.id_depot = :depot_id
        ORDER BY cc.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':depot_id' => $depot_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $commande_id = $_POST['commande_id'];
    
    switch($_POST['action']) {
        case 'valider':
            $sql = "UPDATE commandeclient SET statut = 'En préparation' WHERE id_commande = :commande_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':commande_id' => $commande_id]);
            header("Location: commandes_clients.php?depot=$depot_id&success=1");
            exit();
            break;

        case 'livrer':
            try {
                $conn->beginTransaction();
                
                $sql = "SELECT dc.*, p.nom as nom_produit 
                        FROM detailscommandeclient dc
                        JOIN produit p ON dc.id_produit = p.id_produit
                        WHERE dc.id_commande = :commande_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':commande_id' => $commande_id]);
                $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $total = 0;
                foreach ($details as $detail) {
                    $total += $detail['prix_unit'] * $detail['quantite'];
                    
                    $sql = "UPDATE produit 
                            SET quantite_stock = quantite_stock - :quantite 
                            WHERE id_produit = :produit_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':quantite' => $detail['quantite'],
                        ':produit_id' => $detail['id_produit']
                    ]);
                }
                
                $sql = "UPDATE commandeclient SET total = :total, statut = 'Livrée' WHERE id_commande = :commande_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':total' => $total,
                    ':commande_id' => $commande_id
                ]);
                
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
            break;

        case 'annuler':
            $sql = "UPDATE commandeclient SET statut = 'Annulée' WHERE id_commande = :commande_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':commande_id' => $commande_id]);
            header("Location: commandes_clients.php?depot=$depot_id&success=3");
            exit();
            break;
    }
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
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-buttons form {
            margin: 0;
        }
        .action-buttons .btn {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

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
                                <i class="fas fa-shopping-cart"></i> Commandes Clients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="commandes_fournisseurs.php?depot=<?php echo $depot_id; ?>">
                                <i class="fas fa-truck"></i> Commandes Fournisseurs
                            </a>
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
                                        <td class="action-buttons">
                                            <?php if ($commande['statut'] === 'En attente'): ?>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="valider">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Valider cette commande ?')">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($commande['statut'] === 'En préparation'): ?>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="livrer">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['id_commande']; ?>">
                                                    <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Confirmer la livraison ?')">
                                                        <i class="fas fa-truck"></i> Valider Livraison
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($commande['statut'] === 'Livrée'): ?>
                                                <a href="export_facture.php?id=<?php echo $commande['id_commande']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-file-invoice"></i> Générer Facture
                                                </a>
                                            <?php endif; ?>

                                            <?php if (in_array($commande['statut'], ['En attente', 'En préparation'])): ?>
                                                <form action="" method="POST" class="d-inline">
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
                // Supprimer l'ancien modal s'il existe
                $('#detailsModal').remove();
                
                let modalContent = `
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
                
                let total = 0;
                details.forEach(detail => {
                    const sousTotal = detail.prix_unit * detail.quantite;
                    total += sousTotal;
                    modalContent += `
                        <tr>
                            <td>${detail.nom_produit}</td>
                            <td>${detail.quantite}</td>
                            <td>${detail.prix_unit} FCFA</td>
                            <td>${sousTotal} FCFA</td>
                        </tr>`;
                });
                
                modalContent += `
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>${total} FCFA</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                
                $('body').append(modalContent);
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            });
        }
    </script>
</body>
</html>
