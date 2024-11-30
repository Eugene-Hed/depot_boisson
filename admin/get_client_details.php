<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$client_id = $_GET['id'];

$sql = "SELECT * FROM commandeclient WHERE id_client = :id_client ORDER BY date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_client' => $client_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$details = [
    'commandes' => $commandes
];

header('Content-Type: application/json');
echo json_encode($details);
