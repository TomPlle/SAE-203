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

$id_enseignant_connecte = $_SESSION['user']['id_enseignant']; 
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
// TRAITEMENTS DES DÉCISIONS DE L'ENSEIGNANT
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_decision_stage'])) {
    $id_recherche = (int)$_POST['id_recherche'];
    $decision = $_POST['decision']; 
    $id_etudiant_concerne = (int)$_POST['id_etudiant'];
    $entreprise_cible = $_POST['entreprise_cible'];

    try {
        $pdo->beginTransaction();

        if ($decision === 'accepter') {
            // A. Création automatique d'une ligne entreprise pour satisfaire la contrainte NOT NULL de votre BDD
            $stmtEnt = $pdo->prepare("INSERT INTO entreprise (nom_societe, adresse_siege, tel_contact) VALUES (?, 'Adresse à compléter', '00000000')");
            $stmtEnt->execute([$entreprise_cible]);
            $id_entreprise_auto = $pdo->lastInsertId();

            // B. Création d'un responsable tuteur pro par défaut pour satisfaire la contrainte NOT NULL de votre BDD
            $stmtTut = $pdo->prepare("INSERT INTO responsable_de_stage (nom, prenom, email_pro, password, id_entreprise, grade) VALUES ('A renseigner', 'A renseigner', ?, 'no-password', ?, 'MMI1')");
            $stmtTut->execute(['tuteur_auto_' . $id_recherche . '@gmail.com', $id_entreprise_auto]);
            $id_responsable_auto = $pdo->lastInsertId();

            // C. Insertion de la convention officielle approuvée dans la table STAGE
            $num_convention_unique = 'CONV-' . $id_etudiant_concerne . '-' . time();
            $stmtStage = $pdo->prepare("
                INSERT INTO stage (num_convention, sujet, date_deb, date_fin, etat_validation, id_etudiant, id_enseignant, id_entreprise, id_responsable) 
                VALUES (?, 'Sujet validé par le responsable', ?, ?, 'Validé', ?, ?, ?, ?)
            ");
            $stmtStage->execute([$num_convention_unique, date('Y-m-d'), date('Y-m-d', strtotime('+2 months')), $id_etudiant_concerne, $id_enseignant_connecte, $id_entreprise_auto, $id_responsable_auto]);

            // D. Passage de l'étudiant à valide et mise à jour de son flux historique
            $pdo->prepare("UPDATE etudiant SET valide = 1 WHERE id_etudiant = ?")->execute([$id_etudiant_concerne]);
            $pdo->prepare("UPDATE historique SET reponse = 'Validé' WHERE id_recherche = ?")->execute([$id_recherche]);
            
            $msg_success = "La postulation a été acceptée avec succès, et le dossier de stage officiel a été initialisé !";

        } elseif ($decision === 'refuser') {
            $pdo->prepare("UPDATE historique SET reponse = 'Refusé' WHERE id_recherche = ?")->execute([$id_recherche]);
            $msg_success = "La demande de stage a été rejetée.";
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg_error = "Erreur technique lors du traitement : " . $e->getMessage();
    }
}

// 3. EXTRACTION COMPLÈTE DU FLUX D'HISTORIQUE DE TOUS LES ÉLÈVES DE SA PROMO (S'affiche peu importe si stage créé ou non)
$stmtDemandes = $pdo->prepare("
    SELECT h.id_recherche, h.entreprise_cible, h.date_contact, h.type_action, h.reponse,
           e.id_etudiant, e.nom AS et_nom, e.prenom AS et_prenom, e.email AS et_email, e.gp_td, e.gp_tp
    FROM historique h
    JOIN etudiant e ON h.id_etudiant = e.id_etudiant
    WHERE (e.promo = ? OR REPLACE(e.promo, ' ', '') = REPLACE(?, ' ', ''))
    ORDER BY h.id_recherche DESC
");
$stmtDemandes->execute([$promo_geree, $promo_geree]);
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
    <link class="intranet-favicon" rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
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
        <h2 class="fw-bold text-purple mb-4">Flux Global des Candidatures et Fiches Stages — <?= htmlspecialchars($promo_geree) ?></h2>
        
        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success shadow-sm mb-3"><?= $msg_success ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger shadow-sm mb-3"><?= $msg_error ?></div>
        <?php endif; ?>

        <?php if (empty($demandes_validation)): ?>
            <div class="card p-5 text-center shadow-sm card-custom">Aucune postulation ni démarche enregistrée pour le moment dans la promotion <?= htmlspecialchars($promo_geree) ?>.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($demandes_validation as $d): ?>
                    <div class="col-md-12">
                        <div class="card p-4 shadow-sm card-custom" style="border: 2px solid #8a2be2;">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-3">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($d['et_nom'] . ' ' . $d['et_prenom']) ?></h5>
                                    <div class="small text-muted-custom mb-2"><?= htmlspecialchars($d['et_email']) ?></div>
                                    <span class="badge bg-purple px-2 py-1.5">TD : <?= htmlspecialchars($d['gp_td']) ?> | TP : <?= htmlspecialchars($d['gp_tp']) ?></span>
                                </div>
                                <div class="col-lg-4 border-start border-secondary-subtle">
                                    <h6 class="fw-bold text-purple mb-1"><?= htmlspecialchars($d['entreprise_cible']) ?></h6>
                                    <div class="small text-muted-custom mb-1"><i class="bi bi-calendar me-1"></i> Événement déclaré le : <?= date('d/m/Y', strtotime($d['date_contact'])) ?></div>
                                </div>
                                <div class="col-lg-3 border-start border-secondary-subtle">
                                    <div class="text-muted-custom small text-uppercase mb-1" style="font-size:0.75rem;">Action de l'étudiant</div>
                                    <div class="fw-semibold small text-header-custom"><?= htmlspecialchars($d['type_action']) ?></div>
                                </div>
                                <div class="col-lg-2 text-center border-start border-secondary-subtle">
                                    <?php if ($d['reponse'] === 'En attente de validation responsable'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action_decision_stage" value="1">
                                            <input type="hidden" name="id_recherche" value="<?= $d['id_recherche'] ?>">
                                            <input type="hidden" name="id_etudiant" value="<?= $d['id_etudiant'] ?>">
                                            <input type="hidden" name="entreprise_cible" value="<?= htmlspecialchars($d['entreprise_cible']) ?>">
                                            <button type="submit" name="decision" value="accepter" class="btn btn-success btn-sm w-100 mb-2 fw-bold"><i class="bi bi-check-circle me-1"></i> Approuver</button>
                                            <button type="submit" name="decision" value="refuser" class="btn btn-danger btn-sm w-100 fw-bold"><i class="bi bi-x-circle me-1"></i> Refuser</button>
                                        </form>
                                    <?php elseif ($d['reponse'] === 'Validé'): ?>
                                        <span class="badge bg-success py-2 px-3 w-100 shadow-sm fw-bold"><i class="bi bi-shield-check me-1"></i> CONVENTION APPROUVÉE</span>
                                    <?php elseif ($d['reponse'] === 'Refusé'): ?>
                                        <span class="badge bg-danger py-2 px-3 w-100 shadow-sm fw-bold"><i class="bi bi-x-octagon me-1"></i> REJETÉ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary py-2 px-3 w-100 font-monospace"><?= htmlspecialchars($d['reponse']) ?></span>
                                    <?php endif; ?>
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
</body>
</html>