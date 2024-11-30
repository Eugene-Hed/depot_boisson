<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$id_produit = $_GET['id'];

$sql = "SELECT * FROM produit WHERE id_produit = :id_produit";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_produit' => $id_produit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($produit);
