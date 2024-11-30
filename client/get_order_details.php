<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    http_response_code(403);
    exit();
}

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    exit();
}

// Fetch order details
$sql = "SELECT cc.*, d.nom as depot_nom 
        FROM commandeclient cc 
        JOIN depots d ON cc.id_depot = d.id 
        WHERE cc.id_commande = :id AND cc.id_client = :client_id";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $order_id,
    ':client_id' => $_SESSION['client_id']
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch order items
$sql = "SELECT dc.*, p.nom 
        FROM detailscommandeclient dc 
        JOIN produit p ON dc.id_produit = p.id_produit 
        WHERE dc.id_commande = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $order_id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['order' => $order, 'details' => $details]);
