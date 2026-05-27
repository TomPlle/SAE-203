<?php
session_start();

// Sécurité : Si l'utilisateur n'est pas connecté ou n'est pas enseignant, retour à l'index
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
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

$id_enseignant = $_SESSION['user']['id_enseignant'];
$msg_success = "";
$msg_error = "";

// --- TRAITEMENT DU FORMULAIRE : Mise à jour du rôle uniquement ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_enseignant'])) {
    $role = $_POST['role'];

    try {
        // Sécurité : Seul le champ 'role' est mis à jour. Le nom, prénom et mail restent intacts.
        $stmtUpdate = $pdo->prepare("
            UPDATE enseignant 
            SET role = ?
            WHERE id_enseignant = ?
        ");
        $stmtUpdate->execute([$role, $id_enseignant]);
        
        // Mise à jour de la variable de session du rôle interne si nécessaire
        $_SESSION['user']['role'] = $role;
        
        $msg_success = "Vos informations professionnelles ont été mises à jour avec succès !";
    } catch (PDOException $e) {
        $msg_error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// --- RÉCUPÉRATION DES DONNÉES DE L'ENSEIGNANT ---
$stmtInfo = $pdo->prepare("SELECT * FROM enseignant WHERE id_enseignant = ?");
$stmtInfo->execute([$id_enseignant]);
$enseignant = $stmtInfo->fetch(PDO::FETCH_ASSOC);

// Variables pour l'en-tête et les affichages fixes
$nom_enseignant    = $enseignant['nom'] ?? 'Nom';
$prenom_enseignant = $enseignant['prenom'] ?? 'Prénom';
$role_enseignant   = $enseignant['role'] ?? 'Enseignant';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - Enseignant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">
    <header class="navbar navbar-expand-lg bg-intranet-dark text-white p-0 py-2 border-bottom border-secondary">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="accueil-enseignant.php" style="height: 100%;">
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
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="accueil-enseignant.php">
                            <i class="bi bi-house-door me-2 fs-4"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-stages.php">
                            <i class="bi bi-person-video3 me-2 fs-4"></i> Suivi des Stages
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="soutenances-enseignant.php">
                            <i class="bi bi-calendar-event me-2 fs-4"></i> Soutenances & Notes
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <div class="d-flex align-items-center ps-4">
                        <a class="text-decoration-none" href="compte-enseignant.php">
                            <div class="text-end me-3">
                                <div class="text-muted-custom" style="font-size: 0.7rem;"><center>Espace <?php echo htmlspecialchars($role_enseignant); ?></center></div>
                                <div class="fw-bold text-white text-uppercase" style="font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($prenom_enseignant . ' ' . $nom_enseignant); ?>
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

    <main class="flex-grow-1 container px-4 py-5" style="max-width: 800px;">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-person-vcard text-purple fs-2 me-3"></i>
            <h3 class="fw-bold m-0">Profil & Paramètres Professionnels</h3>
        </div>

        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4"><i class="bi bi-check-circle me-2"></i> <?php echo $msg_success; ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 mb-4"><i class="bi bi-exclamation-triangle me-2"></i> <?php echo $msg_error; ?></div>
        <?php endif; ?>

        <div class="card-custom p-4 border-secondary">
            <form method="POST" action="">
                
                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-person-lock me-2"></i> Identité (Verrouillée)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Nom de famille</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary opacity-50" value="<?php echo htmlspecialchars($nom_enseignant); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted-custom mb-1">Prénom</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary opacity-50" value="<?php echo htmlspecialchars($prenom_enseignant); ?>" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted-custom mb-1">Adresse de messagerie académique</label>
                        <input type="email" class="form-control bg-dark text-white border-secondary opacity-50" value="<?php echo htmlspecialchars($enseignant['email'] ?? ''); ?>" readonly>
                    </div>
                </div>

                <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-shield-check me-2"></i> Rôle & Fonction</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label small text-muted-custom mb-1">Attribution au sein du département MMI</label>
                        <select name="role" class="form-select bg-dark text-white border-secondary" required>
                            <option value="Enseignant" <?php echo ($role_enseignant === 'Enseignant') ? 'selected' : ''; ?>>Enseignant (Tuteur classique)</option>
                            <option value="Jury" <?php echo ($role_enseignant === 'Jury') ? 'selected' : ''; ?>>Jury (Évaluation des soutenances)</option>
                            <option value="Responsable" <?php echo ($role_enseignant === 'Responsable') ? 'selected' : ''; ?>>Responsable des Stages (Pédagogique / Administration)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted-custom mb-1">Statut d'approbation</label>
                        <div class="pt-2">
                            <?php if (($enseignant['valide'] ?? 0) == 1): ?>
                                <span class="badge bg-success px-3 py-2 fs-6 w-100"><i class="bi bi-patch-check-fill me-2"></i> Compte Vérifié</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark px-3 py-2 fs-6 w-100"><i class="bi bi-hourglass-split me-2"></i> En attente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="action_update_enseignant" class="btn btn-purple py-2 fw-bold">
                        <i class="bi bi-save me-2"></i> Mettre à jour mon affectation
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
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</html>