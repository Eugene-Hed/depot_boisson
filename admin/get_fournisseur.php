<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$fournisseur_id = $_GET['id'];

$sql = "SELECT * FROM fournisseur WHERE id_fournisseur = :id_fournisseur";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_fournisseur' => $fournisseur_id]);
$fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($fournisseur);
