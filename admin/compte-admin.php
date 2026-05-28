<?php
session_start();

// 1. Sécurité : Vérifier si l'utilisateur est bien connecté en tant qu'admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit();
}

require_once '../php/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$id_admin = $_SESSION['user']['id_admin'];
$msg_success = "";
$msg_error = "";

// 2. TRAITEMENT DU FORMULAIRE DE MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_admin'])) {
    $email_saisi = trim($_POST['email']);
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Récupérer le mot de passe actuel haché en BDD pour vérification
    $stmtCheck = $pdo->prepare("SELECT password FROM admin WHERE id_admin = ?");
    $stmtCheck->execute([$id_admin]);
    $admin_db = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // Vérification obligatoire du mot de passe actuel
    if (!$admin_db || !password_verify($old_password, $admin_db['password'])) {
        $msg_error = "Erreur : Le mot de passe actuel est incorrect. Les modifications ont été annulées.";
    } else {
        try {
            // Cas 1 : L'admin veut AUSSI changer son mot de passe
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $msg_error = "Erreur : Le nouveau mot de passe et sa confirmation ne correspondent pas.";
                } else {
                    // Hachage sécurisé du nouveau mot de passe
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmtUpdate = $pdo->prepare("UPDATE admin SET email = ?, password = ? WHERE id_admin = ?");
                    $stmtUpdate->execute([$email_saisi, $new_password_hash, $id_admin]);
                    $msg_success = "Votre adresse e-mail et votre mot de passe ont été mis à jour !";
                }
            } 
            // Cas 2 : L'admin change UNIQUEMENT son e-mail
            else {
                $stmtUpdate = $pdo->prepare("UPDATE admin SET email = ? WHERE id_admin = ?");
                $stmtUpdate->execute([$email_saisi, $id_admin]);
                $msg_success = "Votre adresse e-mail a été mise à jour avec succès !";
            }
        } catch (PDOException $e) {
            $msg_error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// 3. RÉCUPÉRATION DES INFOS À JOUR DE L'ADMINISTRATEUR
$stmtInfo = $pdo->prepare("SELECT nom, prenom, email FROM admin WHERE id_admin = ?");
$stmtInfo->execute([$id_admin]);
$admin = $stmtInfo->fetch(PDO::FETCH_ASSOC);

$nom_admin = $admin['nom'] ?? 'Admin';
$prenom_admin = $admin['prenom'] ?? 'Espace';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">

    <header class="navbar navbar-expand-lg bg-intranet-dark text-white p-0 py-2 border-bottom border-secondary">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="dashboard-admin.php" style="height: 100%;">
                <img src="../images/logo-noir-blanc.png" alt="Logo" class="me-3" style="height: 50px; width: auto;"> 
                <div class="lh-sm">
                    <div class="fw-bold text-uppercase" style="font-size: 1.1rem; letter-spacing: 1px;">ESPACE ADMIN</div>
                    <div class="text-muted-custom" style="font-size: 0.8rem; letter-spacing: 0.5px;">UNIVERSITÉ GUSTAVE EIFFEL</div>
                </div>
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="dashboard-admin.php">
                            <i class="bi bi-speedometer2 me-2 fs-4"></i> Vue globale
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-etudiant.php">
                            <i class="bi bi-person-lines-fill me-2 fs-4"></i> Suivi Étudiants
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="creer-admin.php">
                            <i class="bi bi-person-lines-fill me-2 fs-4"></i> Créer un administrateur
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <div class="d-flex align-items-center ps-4">
                        <a class="text-decoration-none" href="compte-admin.php">
                            <div class="text-end me-3">
                                <div class="text-muted-custom" style="font-size: 0.7rem;"><center>Profil Principal</center></div>
                                <div class="fw-bold text-white text-uppercase" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($prenom_admin . ' ' . $nom_admin) ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="ms-2 pe-3">
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>


    <main class="container py-5 flex-grow-1" style="max-width: 800px;">
        <div class="mb-4 d-flex align-items-center">
            <i class="bi bi-person-gear text-purple fs-2 me-3"></i>
            <div>
                <h3 class="fw-bold m-0">Sécurité du Compte Administrateur</h3>
                <p class="text-muted-custom small m-0">Gérez vos identifiants de connexion et protégez l'accès à la plateforme.</p>
            </div>
        </div>

        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $msg_success ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $msg_error ?>
            </div>
        <?php endif; ?>

        <div class="card-custom p-4 border-secondary">
            <form method="POST" action="">
                
                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-lock me-2"></i> Identité (Non modifiable)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Nom de famille</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary opacity-50" value="<?= htmlspecialchars($nom_admin) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Prénom</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary opacity-50" value="<?= htmlspecialchars($prenom_admin) ?>" readonly>
                    </div>
                </div>

                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-pencil-square me-2"></i> Identifiants éditables</h5>
                <div class="mb-4">
                    <label class="form-label small text-muted-custom mb-1">Adresse e-mail de connexion</label>
                    <input type="email" name="email" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="form-control bg-dark text-white border-secondary" placeholder="Laisser vide si inchangé">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" class="form-control bg-dark text-white border-secondary" placeholder="Laisser vide si inchangé">
                    </div>
                </div>

                <div class="bg-intranet-dark p-3 rounded border border-warning mb-4" style="box-shadow: 0 0 10px rgba(255, 193, 7, 0.05);">
                    <label class="form-label small text-warning fw-bold mb-1">
                        <i class="bi bi-shield-lock-fill me-1"></i> Saisie du mot de passe actuel obligatoire
                    </label>
                    <p class="text-muted-custom small mb-2">Pour enregistrer vos modifications (e-mail ou mot de passe), merci de confirmer votre identité.</p>
                    <input type="password" name="old_password" class="form-control bg-dark text-white border-warning" placeholder="Entrez votre mot de passe actuel" required>
                </div>

                <div class="d-grid">
                    <button type="submit" name="action_update_admin" class="btn btn-purple py-2 fw-bold">
                        <i class="bi bi-save me-2"></i> Enregistrer les modifications du profil
                    </button>
                </div>

            </form>
        </div>
    </main>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-12 text-start">
                    <p class="m-0 text-muted-custom" style="font-size: 0.85rem; font-family: sans-serif;">
                        &copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>