<?php
require_once 'includes/database.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Dépôt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Dépôt Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                    <a class="nav-link" href="auth/connexion.php">
        <i class="fas fa-sign-in-alt"></i> Connexion
    </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/inscription.php">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <h1>Bienvenue sur Dépôt Manager</h1>
                <p class="lead">Votre solution complète pour la gestion de stock et de commandes</p>
                <ul class="list-group mt-4">
                    <li class="list-group-item"><i class="fas fa-check text-success"></i> Gestion des stocks en temps réel</li>
                    <li class="list-group-item"><i class="fas fa-check text-success"></i> Suivi des commandes clients</li>
                    <li class="list-group-item"><i class="fas fa-check text-success"></i> Gestion des fournisseurs</li>
                    <li class="list-group-item"><i class="fas fa-check text-success"></i> Rapports détaillés</li>
                </ul>
            </div>
            <div class="col-md-6">
                <img src="assets/img/warehouse.jpg" alt="Entrepôt" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
