
<?php
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telephone = htmlspecialchars($_POST['telephone']);
    $role = htmlspecialchars($_POST['role']);

    try {
        $conn->beginTransaction();
        
        // Insert into utilisateur table
        $sql = "INSERT INTO utilisateur (nom, email, mot_de_passe, telephone, role) 
                VALUES (:nom, :email, :password, :telephone, :role)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':password' => $password,
            ':telephone' => $telephone,
            ':role' => $role
        ]);
        
        $userId = $conn->lastInsertId();

        // If role is Client, create client record
        if ($role === 'Client') {
            $sql = "INSERT INTO client (nom, email, telephone, mot_de_passe, adresse, id_utilisateur) 
                    VALUES (:nom, :email, :telephone, :password, :adresse, :id_utilisateur)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':telephone' => $telephone,
                ':password' => $password,
                ':adresse' => 'Non renseignée', // Default value for required address field
                ':id_utilisateur' => $userId
            ]);
        }
        $conn->commit();
        header('Location: connexion.php?success=1');
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Une erreur est survenue lors de l'inscription";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Gestion de Dépôt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Inscription</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form action="" method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type de compte</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleClient" value="Client" checked>
                                    <label class="form-check-label" for="roleClient">
                                        Client
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="Admin">
                                    <label class="form-check-label" for="roleAdmin">
                                        Propriétaire de dépôt
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">S'inscrire</button>
                                <a href="../index.php" class="btn btn-secondary">Retour</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                            Déjà inscrit? <a href="connexion.php">Connectez-vous</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
