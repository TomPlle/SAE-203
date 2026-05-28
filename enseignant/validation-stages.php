<?php
session_start();

// 1. SÉCURITÉ : Vérifier si l'utilisateur est bien connecté en tant qu'enseignant
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
$nom_enseignant = $_SESSION['user']['nom'] ?? 'Nom';
$prenom_enseignant = $_SESSION['user']['prenom'] ?? 'Prénom';
$role_enseignant = $_SESSION['user']['role'] ?? 'Enseignant';

// 2. VÉRIFICATION DROIT RESPONSABLE
$est_responsable = false;
$promo_geree = "";

if (stripos($role_enseignant, 'Responsable-Stage-MMI1') !== false) { $est_responsable = true; $promo_geree = "MMI 1"; }
elseif (stripos($role_enseignant, 'Responsable-Stage-MMI2') !== false) { $est_responsable = true; $promo_geree = "MMI 2"; }
elseif (stripos($role_enseignant, 'Responsable-Stage-MMI3') !== false) { $est_responsable = true; $promo_geree = "MMI 3"; }

if (!$est_responsable) {
    header("Location: accueil-enseignant.php");
    exit();
}

$est_un_responsable = $est_responsable;
$msg_success = "";
$msg_error = "";

// -------------------------------------------------------------------------
// TRAITEMENTS DES DÉCISIONS
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_decision_stage'])) {
    $id_stage = (int)$_POST['id_stage'];
    $decision = $_POST['decision']; 
    $id_etudiant_concerne = (int)$_POST['id_etudiant'];

    try {
        $pdo->beginTransaction();

        if ($decision === 'accepter') {
            $pdo->prepare("UPDATE stage SET etat_validation = '1' WHERE id_stage = ?")->execute([$id_stage]);
            $pdo->prepare("UPDATE etudiant SET valide = 1 WHERE id_etudiant = ?")->execute([$id_etudiant_concerne]);
            $pdo->prepare("UPDATE historique SET reponse = 'Validé' WHERE id_etudiant = ? AND reponse = 'En attente de validation responsable' ORDER BY id_recherche DESC LIMIT 1")->execute([$id_etudiant_concerne]);
            $msg_success = "Le stage de l'étudiant a été officiellement approuvé !";
        } elseif ($decision === 'refuser') {
            $pdo->prepare("UPDATE stage SET etat_validation = '2' WHERE id_stage = ?")->execute([$id_stage]);
            $pdo->prepare("UPDATE historique SET reponse = 'Refusé' WHERE id_etudiant = ? AND reponse = 'En attente de validation responsable' ORDER BY id_recherche DESC LIMIT 1")->execute([$id_etudiant_concerne]);
            $msg_success = "La demande de stage a été refusée.";
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg_error = "Erreur : " . $e->getMessage();
    }
}

// 3. RÉCUPÉRATION DES STAGES EN ATTENTE (etat_validation = 0)
$stmtDemandes = $pdo->prepare("
    SELECT s.id_stage, s.num_convention, s.sujet, s.date_deb, s.date_fin,
           e.id_etudiant, e.nom AS et_nom, e.prenom AS et_prenom, e.email AS et_email, e.gp_td, e.gp_tp,
           ent.nom_societe, ent.adresse_siege, ent.tel_contact
    FROM stage s
    JOIN etudiant e ON s.id_etudiant = e.id_etudiant
    JOIN entreprise ent ON s.id_entreprise = ent.id_entreprise
    WHERE e.promo = ? AND (s.etat_validation = '0' OR s.etat_validation = 0)
    ORDER BY s.id_stage ASC
");
$stmtDemandes->execute([$promo_geree]);
$demandes_validation = $stmtDemandes->fetchAll(PDO::FETCH_ASSOC);

$total_demandes = count($demandes_validation);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des Stages</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary small">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="accueil-enseignant.php" style="font-size: 0.85rem;">
                            <i class="bi bi-house-door me-2 fs-6"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-stages.php" style="font-size: 0.85rem;">
                            <i class="bi bi-person-video3 me-2 fs-6"></i> Suivi des Stages
                        </a>
                    </li>
                    
                    <?php if ($est_un_responsable): ?>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="validation-stages.php" style="font-size: 0.85rem;">
                            <i class="bi bi-clipboard-check me-2 fs-6"></i> Demandes de Validation
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="soutenances-enseignant.php" style="font-size: 0.85rem;">
                            <i class="bi bi-calendar-event me-2 fs-6"></i> Soutenances & Notes
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="offres-enseignant.php" style="font-size: 0.85rem;">
                            <i class="bi bi-grid-3x3-gap me-2 fs-6"></i> Catalogue Offres
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

    <main class="container py-5 flex-grow-1">
        <h2 class="fw-bold text-purple mb-4">Approbation des stages — <?= htmlspecialchars($promo_geree) ?></h2>
        
        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success"><?= $msg_success ?></div>
        <?php endif; ?>

        <?php if (empty($demandes_validation)): ?>
            <div class="card p-5 text-center">Aucune demande en attente.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($demandes_validation as $d): ?>
                    <div class="col-md-12">
                        <div class="card p-4 shadow-sm" style="border: 2px solid #8a2be2;">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-3">
                                    <h5 class="fw-bold"><?= htmlspecialchars($d['et_nom'] . ' ' . $d['et_prenom']) ?></h5>
                                    <div class="small text-info"><?= htmlspecialchars($d['et_email']) ?></div>
                                    <span class="badge bg-dark mt-2">TD : <?= htmlspecialchars($d['gp_td']) ?> | TP : <?= htmlspecialchars($d['gp_tp']) ?></span>
                                </div>
                                <div class="col-lg-4 border-start">
                                    <h6 class="fw-bold text-purple"><?= htmlspecialchars($d['nom_societe']) ?></h6>
                                    <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($d['adresse_siege']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($d['tel_contact']) ?></div>
                                </div>
                                <div class="col-lg-3 border-start">
                                    <h6 class="fw-bold"><?= htmlspecialchars($d['sujet']) ?></h6>
                                    <div class="small font-monospace"><i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($d['date_deb'])) ?> au <?= date('d/m/Y', strtotime($d['date_fin'])) ?></div>
                                </div>
                                <div class="col-lg-2">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_stage" value="<?= $d['id_stage'] ?>">
                                        <input type="hidden" name="id_etudiant" value="<?= $d['id_etudiant'] ?>">
                                        <button type="submit" name="decision" value="accepter" class="btn btn-success btn-sm w-100 mb-2">Approuver</button>
                                        <button type="submit" name="decision" value="refuser" class="btn btn-danger btn-sm w-100">Refuser</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4"><p class="m-0 text-muted-custom" style="font-size: 0.85rem;">&copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel</p></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSearchInputs() {
            const searchType = document.getElementById('search_type').value;
            const textWrapper = document.getElementById('wrapper_text_input');
            const textInput = document.getElementById('search_query');
            const statusWrapper = document.getElementById('wrapper_status_select');
            const statusSelect = document.getElementById('search_status');

            if (searchType === 'statut') {
                textWrapper.style.display = 'none';
                textInput.disabled = true;
                statusWrapper.style.display = 'block';
                statusSelect.disabled = false;
            } else {
                textWrapper.style.display = 'block';
                textInput.disabled = false;
                statusWrapper.style.display = 'none';
                statusSelect.disabled = true;
            }
        }
        document.addEventListener("DOMContentLoaded", toggleSearchInputs);

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