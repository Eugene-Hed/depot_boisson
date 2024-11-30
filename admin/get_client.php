<?php
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Accès non autorisé');
}

$client_id = $_GET['id'];

$sql = "SELECT * FROM client WHERE id_client = :id_client";
$stmt = $conn->prepare($sql);
$stmt->execute([':id_client' => $client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($client);
