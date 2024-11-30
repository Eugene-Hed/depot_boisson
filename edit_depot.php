<?php
require_once 'includes/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch depot details
$depot_id = $_GET['id'] ?? null;
if (!$depot_id) {
    header('Location: mes_depots.php');
    exit();
}

$sql = "SELECT * FROM depots WHERE id = :id AND id_proprietaire = :id_proprietaire";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $depot_id,
    ':id_proprietaire' => $_SESSION['user_id']
]);
$depot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$depot) {
    header('Location: mes_depots.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $contact = htmlspecialchars($_POST['contact']);
    
    // Logo upload handling
    $logo_path = $depot['logo'];
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_dir = __DIR__ . '/uploads/logos/';
            $upload_path = $upload_dir . $new_filename;
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                if($logo_path && file_exists($logo_path)) {
                    unlink($logo_path);
                }
                $logo_path = 'uploads/logos/' . $new_filename;
            }
        }
    }

    $sql = "UPDATE depots SET nom = :nom, adresse = :adresse, contact = :contact, logo = :logo 
            WHERE id = :id AND id_proprietaire = :id_proprietaire";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':adresse' => $adresse,
        ':contact' => $contact,
        ':logo' => $logo_path,
        ':id' => $depot_id,
        ':id_proprietaire' => $_SESSION['user_id']
    ]);

    header('Location: mes_depots.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Dépôt - Gestion de Dépôt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .depot-form {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
        }
        .logo-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .custom-file-upload {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .custom-file-upload:hover {
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="mb-4">
                    <h2 class="display-6">Modifier le dépôt</h2>
                    <p class="text-muted">Mettez à jour les informations de votre dépôt</p>
                </div>

                <div class="depot-form shadow-sm">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom du dépôt</label>
                                <input type="text" class="form-control form-control-lg" id="nom" name="nom" 
                                       value="<?php echo htmlspecialchars($depot['nom']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact" class="form-label">Contact principal</label>
                                <input type="tel" class="form-control form-control-lg" id="contact" name="contact"
                                       value="<?php echo htmlspecialchars($depot['contact']); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="adresse" class="form-label">Adresse complète</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3" required>
                                <?php echo htmlspecialchars($depot['adresse']); ?>
                            </textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Logo du dépôt</label>
                            <?php if($depot['logo']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($depot['logo']); ?>" 
                                         class="logo-preview" alt="Logo actuel">
                                </div>
                            <?php endif; ?>
                            <div class="custom-file-upload" id="logoUpload">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p class="mb-0">Cliquez pour changer le logo</p>
                                <input type="file" name="logo" id="logo" class="d-none" accept="image/*">
                            </div>
                            <img id="newLogoPreview" class="logo-preview d-none">
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="mes_depots.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('logoUpload').addEventListener('click', () => {
            document.getElementById('logo').click();
        });

        document.getElementById('logo').addEventListener('change', function(e) {
            if(this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('newLogoPreview');
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>
