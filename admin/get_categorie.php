<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$categorie_id = $_GET['id'];

$sql = "SELECT * FROM categorie WHERE id_categorie = :id_categorie";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_categorie' => $categorie_id]);
$categorie = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($categorie);
