<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$commande_id = $_GET['id'];

$sql = "SELECT dcc.*, p.nom as nom_produit 
        FROM detailscommandeclient dcc
        JOIN produit p ON dcc.id_produit = p.id_produit
        WHERE dcc.id_commande = :commande_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':commande_id' => $commande_id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($details);
