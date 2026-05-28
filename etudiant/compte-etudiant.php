<?php
session_start();

// Sécurité : Si l'étudiant n'est pas connecté ou n'a pas le bon rôle, retour à l'index
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: ../index.html");
    exit();
}

require_once '../php/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$id_etudiant = $_SESSION['user']['id_etudiant'];
$msg_success = "";
$msg_error = "";

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_compte'])) {
    $tel        = $_POST['tel'];
    $adresse    = $_POST['adresse'];
    $promo      = $_POST['promo'];
    $gp_td      = $_POST['gp_td'];
    $gp_tp      = $_POST['gp_tp'];
    $email      = trim($_POST['email']);
    $old_password     = $_POST['old_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Récupération du mot de passe actuel stocké en BDD pour validation de sécurité
    $stmtCheck = $pdo->prepare("SELECT password FROM etudiant WHERE id_etudiant = ?");
    $stmtCheck->execute([$id_etudiant]);
    $etudiant_db = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant_db || !password_verify($old_password, $etudiant_db['password'])) {
        $msg_error = "Erreur : Le mot de passe actuel est incorrect. Les modifications ont été annulées.";
    } else {
        try {
            // Vérification si l'utilisateur souhaite également modifier son mot de passe
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $msg_error = "Erreur : Le nouveau mot de passe et sa confirmation ne correspondent pas.";
                } else {
                    // Hachage sécurisé du nouveau mot de passe
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmtUpdate = $pdo->prepare("
                        UPDATE etudiant 
                        SET tel = ?, adresse = ?, promo = ?, gp_td = ?, gp_tp = ?, email = ?, password = ?
                        WHERE id_etudiant = ?
                    ");
                    $stmtUpdate->execute([$tel, $adresse, $promo, $gp_td, $gp_tp, $email, $new_password_hash, $id_etudiant]);
                    $msg_success = "Vos données personnelles, votre e-mail et votre mot de passe ont été mis à jour !";
                }
            } else {
                // Mise à jour classique sans changement de mot de passe
                $stmtUpdate = $pdo->prepare("
                    UPDATE etudiant 
                    SET tel = ?, adresse = ?, promo = ?, gp_td = ?, gp_tp = ?, email = ?
                    WHERE id_etudiant = ?
                ");
                $stmtUpdate->execute([$tel, $adresse, $promo, $gp_td, $gp_tp, $email, $id_etudiant]);
                $msg_success = "Vos données personnelles et votre adresse e-mail ont été mises à jour avec succès !";
            }
        } catch (PDOException $e) {
            $msg_error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// --- RÉCUPÉRATION DES DONNÉES DE L'ÉTUDIANT ---
$stmtInfo = $pdo->prepare("SELECT * FROM etudiant WHERE id_etudiant = ?");
$stmtInfo->execute([$id_etudiant]);
$etudiant = $stmtInfo->fetch(PDO::FETCH_ASSOC);

// Variables de sécurité pour l'affichage de l'en-tête
$nom_etudiant    = $etudiant['nom'] ?? 'Nom';
$prenom_etudiant = $etudiant['prenom'] ?? 'Prénom';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - Etudiant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
    <script>
        const themeEnregistre = localStorage.getItem('intranet-theme') || 'light';
        if (themeEnregistre === 'dark') {
            document.documentElement.classList.add('dark-theme-init');
        }
    </script>
</head>
<body id="page-body" class="d-flex flex-column min-vh-100 light-mode">
    
    <script>
        if (localStorage.getItem('intranet-theme') === 'dark') {
            const bodyEl = document.getElementById('page-body');
            bodyEl.classList.remove('light-mode');
            bodyEl.classList.add('dark-mode');
        }
    </script>
    <header class="navbar navbar-expand-lg bg-intranet-dark text-white p-0 py-2 border-bottom border-secondary">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="accueil-etudiant.php" style="height: 100%;">
                <img src="../images/logo-noir-blanc.png" alt="Logo" class="me-3" style="height: 50px; width: auto;"> 
                <div class="lh-sm">
                    <div class="fw-bold text-uppercase" style="font-size: 1.1rem; letter-spacing: 1px;">
                        GESTIONNAIRE DE STAGE
                    </div>
                    <div class="text-muted-custom" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        UNIVERSITÉ GUSTAVE EIFFEL
                    </div>
                </div>
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="accueil-etudiant.php">
                            <i class="bi bi-house-door me-2 fs-4"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="demarches-etudiant.php">
                            <i class="bi bi-folder me-2 fs-4"></i> Démarches
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="offres-etudiant.php">
                            <i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Offres
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <div class="pe-4">
                        <button id="themeChangerBtn" class="theme-switch-btn" title="Changer le mode de couleur">
                            <i id="iconeTheme" class="bi bi-moon-stars-fill text-white"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center ps-4">
                        <a class="text-decoration-none" href="compte-etudiant.php">
                            <div class="text-end me-3">
                                <div class="text-muted-custom" style="font-size: 0.7rem;"><center>Profil</center></div>
                                <div class="fw-bold text-white text-uppercase" style="font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($prenom_etudiant . ' ' . $nom_etudiant); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="ms-2 pe-3">
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm" title="Déconnexion"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow-1 container px-4 py-5" style="max-width: 900px;">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-person-gear text-purple fs-2 me-3"></i>
            <h3 class="fw-bold m-0">Mes données personnelles</h3>
        </div>

        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4"><i class="bi bi-check-circle me-2"></i> <?php echo $msg_success; ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 mb-4"><i class="bi bi-exclamation-triangle me-2"></i> <?php echo $msg_error; ?></div>
        <?php endif; ?>

        <div class="card-custom p-4 border-secondary">
            <form method="POST" action="">
                
                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-mortarboard me-2"></i> Scolarité</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small text-muted-custom mb-1">Numéro de Matricule (Bloqué)</label>
                        <input type="text" class="form-control border-secondary opacity-50" value="<?php echo htmlspecialchars($etudiant['matricule'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted-custom mb-1">Promotion</label>
                        <select name="promo" class="form-select border-secondary" required>
                            <option value="MMI 1" <?php echo (($etudiant['promo'] ?? '') === 'MMI 1') ? 'selected' : ''; ?>>MMI 1</option>
                            <option value="MMI 2" <?php echo (($etudiant['promo'] ?? '') === 'MMI 2') ? 'selected' : ''; ?>>MMI 2</option>
                            <option value="MMI 3" <?php echo (($etudiant['promo'] ?? '') === 'MMI 3') ? 'selected' : ''; ?>>MMI 3</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted-custom mb-1">Groupe TD</label>
                        <input type="text" name="gp_td" class="form-control border-secondary text-center" value="<?php echo htmlspecialchars($etudiant['gp_td'] ?? ''); ?>" placeholder="Ex: 3">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted-custom mb-1">Groupe TP</label>
                        <input type="text" name="gp_tp" class="form-control border-secondary text-center" value="<?php echo htmlspecialchars($etudiant['gp_tp'] ?? ''); ?>" placeholder="Ex: e">
                    </div>
                </div>

                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-person me-2"></i> État Civil & Contact</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Nom de famille (Bloqué)</label>
                        <input type="text" class="form-control border-secondary opacity-50" value="<?php echo htmlspecialchars($nom_etudiant); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Prénom (Bloqué)</label>
                        <input type="text" class="form-control border-secondary opacity-50" value="<?php echo htmlspecialchars($prenom_etudiant); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Date de naissance (Bloquée)</label>
                        <input type="text" class="form-control border-secondary opacity-50" value="<?php echo !empty($etudiant['date_naiss']) ? date('d/m/Y', strtotime($etudiant['date_naiss'])) : 'Non renseignée'; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Numéro de téléphone</label>
                        <input type="tel" name="tel" class="form-control border-secondary" value="<?php echo htmlspecialchars($etudiant['tel'] ?? ''); ?>" placeholder="Ex: 06XXXXXXXX">
                    </div>
                </div>

                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-shield-lock me-2"></i> Sécurité & Authentification</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label small text-muted-custom mb-1">Adresse e-mail</label>
                        <input type="email" name="email" class="form-control border-secondary" value="<?php echo htmlspecialchars($etudiant['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="form-control border-secondary" placeholder="Laisser vide si inchangé">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" class="form-control border-secondary" placeholder="Laisser vide si inchangé">
                    </div>
                </div>

                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-house me-2"></i> Coordonnées Postales</h5>
                <div class="mb-4">
                    <label class="form-label small text-muted-custom mb-1">Adresse postale complète</label>
                    <textarea name="adresse" rows="2" class="form-control border-secondary" placeholder="Numéro, rue, code postal et ville..."><?php echo htmlspecialchars($etudiant['adresse'] ?? ''); ?></textarea>
                </div>

                <div class="bg-intranet-dark p-3 rounded border border-warning mb-4" style="box-shadow: 0 0 10px rgba(255, 193, 7, 0.05); background-color: rgba(0,0,0,0.2);">
                    <label class="form-label small text-warning fw-bold mb-1">
                        <i class="bi bi-shield-lock-fill me-1"></i> Validation requise
                    </label>
                    <p class="text-muted-custom small mb-2">Veuillez renseigner votre mot de passe actuel pour confirmer et enregistrer l'ensemble des modifications.</p>
                    <input type="password" name="old_password" class="form-control border-warning" placeholder="Entrez votre mot de passe actuel" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="action_update_compte" class="btn btn-purple py-2 fw-bold">
                        <i class="bi bi-save me-2"></i> Enregistrer mes modifications
                    </button>
                </div>

            </form>
        </div>
    </main>

    <footer class="bg-black text-white py-2 border-top border-secondary">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const themeChangerBtn = document.getElementById('themeChangerBtn');
        const iconeTheme = document.getElementById('iconeTheme');

        function verifierIconeVisualisation() {
            if (document.body.classList.contains('light-mode')) {
                iconeTheme.className = 'bi bi-moon-stars-fill text-white'; 
            } else {
                iconeTheme.className = 'bi bi-sun-fill text-warning'; 
            }
        }

        verifierIconeVisualisation();

        themeChangerBtn.addEventListener('click', () => {
            if (document.body.classList.contains('light-mode')) {
                document.body.classList.remove('light-mode');
                document.body.classList.add('dark-mode');
                localStorage.setItem('intranet-theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                document.body.classList.add('light-mode');
                localStorage.setItem('intranet-theme', 'light');
            }
            verifierIconeVisualisation();
        });
    </script>
</body>
</html>