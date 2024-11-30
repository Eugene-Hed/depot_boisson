<?php
$host = "localhost";
$db_name = "depot";
$username = "root";
$password = "Hedric&2002";
$conn = null;

try {
    $conn = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name,
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>