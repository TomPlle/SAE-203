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

// Vérification : l'enseignant connecté est-il responsable d'une promotion ?
$est_un_responsable = (strpos($role_enseignant, 'Responsable-stage-MMI') !== false || strpos($role_enseignant, 'Responsable-Stage-MMI') !== false);

// Variable de droits exclusifs
$est_responsable_mmi1 = ($role_enseignant === 'Responsable-Stage-MMI1' || $role_enseignant === 'Responsable-stage-MMI1');

$msg_success = "";
$msg_error = "";

// -------------------------------------------------------------------------
// TRAITEMENTS DES SOUMISSIONS DE FORMULAIRES (RÉSERVÉS AU RESPONSABLE MMI1)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$est_responsable_mmi1) {
    $msg_error = "Action refusée : Vous n'avez pas les droits Responsable-Stage-MMI1.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $est_responsable_mmi1) {

    // ACTION A : Saisie d'une offre de stage
    if (isset($_POST['action_creer_offre'])) {
        $intitule = $_POST['intitule'];
        $desc = $_POST['description'];
        $comp = $_POST['competences'];
        $duree = $_POST['duree'];
        $lieu = $_POST['lieu'];
        $remun = !empty($_POST['remuneration']) ? $_POST['remuneration'] : 0;
        
        $stmt = $pdo->prepare("INSERT INTO offre (intitule, description, competences, duree, lieu, remuneration, promotion_visee) VALUES (?, ?, ?, ?, ?, ?, 'MMI1')");
        $stmt->execute([$intitule, $desc, $comp, $duree, $lieu, $remun]);
        $msg_success = "Nouvelle offre de stage publiée pour les MMI1 !";
    }

    // ACTION B : Affecter un étudiant à un stage
    if (isset($_POST['action_affecter_etudiant'])) {
        $id_et = $_POST['id_etudiant'];
        $id_ent = $_POST['id_entreprise'];
        $id_resp = $_POST['id_responsable'];
        $num_conv = $_POST['num_convention'];
        $sujet = $_POST['sujet_stage'];
        $dt_deb = $_POST['date_deb'];
        $dt_fin = $_POST['date_fin'];

        $stmt = $pdo->prepare("INSERT INTO stage (num_convention, sujet, date_deb, date_fin, etat_validation, id_etudiant, id_enseignant, id_entreprise, id_responsable) VALUES (?, ?, ?, ?, 'En cours', ?, ?, ?, ?)");
        $stmt->execute([$num_conv, $sujet, $dt_deb, $dt_fin, $id_et, $id_enseignant, $id_ent, $id_resp]);
        
        $pdo->prepare("UPDATE etudiant SET valide = 1 WHERE id_etudiant = ?")->execute([$id_et]);
        $pdo->prepare("UPDATE historique SET reponse = 'Validé' WHERE id_etudiant = ? AND reponse = 'En attente' ORDER BY id_recherche DESC LIMIT 1")->execute([$id_et]);
        
        $msg_success = "Étudiant affecté et convention de stage créée !";
    }

    // ACTION C : Saisir une démarche de recherche
    if (isset($_POST['action_saisir_recherche'])) {
        $id_et = $_POST['id_etudiant'];
        $ent_cible = $_POST['entreprise_cible'];
        $type_act = $_POST['type_action'];
        $rep = $_POST['reponse'];
        $dt_contact = date('Y-m-d');

        $stmt = $pdo->prepare("INSERT INTO historique (entreprise_cible, date_contact, type_action, reponse, id_etudiant) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ent_cible, $dt_contact, $type_act, $rep, $id_et]);
        $msg_success = "Nouvelle démarche de recherche ajoutée.";
    }

    // ACTION D : Organisation d'un oral
    if (isset($_POST['action_planifier_oral'])) {
        $id_et = $_POST['id_etudiant'];
        $dt_sout = $_POST['date_soutenance'];
        $hr_sout = $_POST['heure_soutenance'];
        $salle = $_POST['salle'];
        $j1 = $_POST['jury1'];
        $j2 = $_POST['jury2'];

        $stmt = $pdo->prepare("INSERT INTO soutenance (date, heure, salle, id_etudiant, id_enseignant_1, id_enseignant_2) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dt_sout, $hr_sout, $salle, $id_et, $j1, $j2]);
        $msg_success = "Soutenance planifiée avec succès !";
    }

    // ACTION E : Mettre à jour le suivi ou signaler un problème
    if (isset($_POST['action_update_suivi'])) {
        $id_stage = $_POST['id_stage'];
        $date_visite = !empty($_POST['date_visite']) ? $_POST['date_visite'] : null;
        $etat_validation = $_POST['etat_validation'];

        $stmtUpdate = $pdo->prepare("UPDATE stage SET date_visite = ?, etat_validation = ? WHERE id_stage = ?");
        $stmtUpdate->execute([$date_visite, $etat_validation, $id_stage]);
        $msg_success = "Dossier de suivi étudiant mis à jour !";
    }
}

// -------------------------------------------------------------------------
// INITIALISATION DES FILTRES DE LA PAGE (REPRIS DE L'ADMIN)
// -------------------------------------------------------------------------
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'prenom';
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : 'En attente';
$search_promo = isset($_GET['search_promo']) ? $_GET['search_promo'] : 'toutes';

// Récupération de tous les étudiants validés (Structure Admin)
$sql = "SELECT id_etudiant, matricule, nom, prenom, email, promo, gp_td, gp_tp FROM etudiant WHERE valide = 1 ORDER BY nom ASC, prenom ASC";
$stmtAll = $pdo->query($sql);
$tous_les_etudiants = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

$mmi1 = [];
$mmi2 = [];
$mmi3 = [];

// Filtrage et distribution par promotion
foreach ($tous_les_etudiants as $e) {
    $stmtDernier = $pdo->prepare("SELECT reponse FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC LIMIT 1");
    $stmtDernier->execute([$e['id_etudiant']]);
    $derniere_action = $stmtDernier->fetch(PDO::FETCH_ASSOC);
    
    $statut_actuel = "En attente"; 
    if ($derniere_action) {
        $statut_actuel = $derniere_action['reponse'];
    }
    $e['statut_actuel'] = $statut_actuel;

    $valide_critere = false;
    if ($search_type === 'statut') {
        if ($statut_actuel === $search_status) {
            $valide_critere = true;
        }
    } else {
        if (!empty($search_query)) {
            if ($search_type === 'prenom' && stripos($e['prenom'], $search_query) !== false) {
                $valide_critere = true;
            } elseif ($search_type === 'matricule' && stripos($e['matricule'], $search_query) !== false) {
                $valide_critere = true;
            }
        } else {
            $valide_critere = true;
        }
    }

    if ($valide_critere) {
        if ($e['promo'] === 'MMI 1' || $e['promo'] === 'MMI1') { $mmi1[] = $e; }
        elseif ($e['promo'] === 'MMI 2' || $e['promo'] === 'MMI2') { $mmi2[] = $e; }
        elseif ($e['promo'] === 'MMI 3' || $e['promo'] === 'MMI3') { $mmi3[] = $e; }
    }
}

// Compteurs statistiques pour les Chips globales
$total_mmi1 = $pdo->query("SELECT COUNT(*) FROM etudiant WHERE promo = 'MMI 1' OR promo = 'MMI1'")->fetchColumn();
$total_valide = $pdo->query("SELECT COUNT(*) FROM etudiant WHERE (promo = 'MMI 1' OR promo = 'MMI1') AND valide = 1")->fetchColumn();
$total_en_recherche = $total_mmi1 - $total_valide;
$total_alertes = $pdo->query("SELECT COUNT(*) FROM stage s JOIN etudiant et ON s.id_etudiant = et.id_etudiant WHERE s.etat_validation = 'Problème'")->fetchColumn();

// Listes pour alimenter les listes déroulantes des Modals
$liste_etudiants_mmi1 = $pdo->query("SELECT id_etudiant, nom, prenom FROM etudiant WHERE promo='MMI 1' OR promo='MMI1' ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$liste_entreprises = $pdo->query("SELECT id_entreprise, nom_societe FROM `entreprise` ORDER BY nom_societe ASC")->fetchAll(PDO::FETCH_ASSOC);
$liste_tuteurs_pro = $pdo->query("SELECT id_responsable, nom, prenom FROM responsable_de_stage ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$liste_profs = $pdo->query("SELECT id_enseignant, nom, prenom FROM enseignant ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// Gestion de la mise en page
$column_class = ($search_promo === 'toutes') ? 'col-xl-4 col-md-6 col-12' : 'col-12';

// -------------------------------------------------------------------------
// REPRIS DE L'ADMIN : FONCTION UTILITAIRE MODIFIÉE POUR INTÉGRER LA GESTION DES STAGES
// -------------------------------------------------------------------------
function afficher_liste_suivi_responsable($liste_etudiants, $pdo, $est_responsable_mmi1) {
    foreach ($liste_etudiants as $e) {
        // 1. Récupération historique
        $stmtDemarches = $pdo->prepare("SELECT date_contact, entreprise_cible, type_action, reponse FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC");
        $stmtDemarches->execute([$e['id_etudiant']]);
        $demarches = $stmtDemarches->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Récupération données du stage s'il existe
        $stmtStage = $pdo->prepare("SELECT s.*, ent.nom_societe, r.nom AS r_nom, r.prenom AS r_prenom FROM stage s LEFT JOIN entreprise ent ON s.id_entreprise = ent.id_entreprise LEFT JOIN responsable_de_stage r ON s.id_responsable = r.id_responsable WHERE s.id_etudiant = ? LIMIT 1");
        $stmtStage->execute([$e['id_etudiant']]);
        $stage_data = $stmtStage->fetch(PDO::FETCH_ASSOC);

        $collapseId = "collapse_" . $e['id_etudiant'];
        $headingId = "heading_" . $e['id_etudiant'];
        $promoIdClean = str_replace(' ', '', $e['promo']);

        // Gestion de la couleur des badges selon l'état d'alerte ou de validation
        if ($stage_data && $stage_data['etat_validation'] === 'Problème') {
            $card_badge_class = "bg-danger text-white";
            $status_text = "🚨 Problème signalé";
            $custom_border = "border: 2px solid #ff0055; box-shadow: 0 0 10px rgba(255,0,85,0.15);";
        } else {
            $card_badge_class = ($e['statut_actuel'] === 'Validé' || $e['statut_actuel'] === 'Convention signée') ? "bg-success text-white" : (($e['statut_actuel'] === 'Refusé') ? "bg-danger text-white" : "bg-warning text-dark");
            $status_text = $e['statut_actuel'];
            $custom_border = "border: 2px solid #8a2be2; box-shadow: 0 0 10px rgba(138,43,226,0.15);";
        }
        ?>
        <div class="card-custom mb-3 overflow-hidden p-1 shadow-lg" style="<?= $custom_border ?>">
            <div class="p-3 bg-intranet-dark text-white border-bottom border-secondary-subtle" 
                  id="<?= $headingId ?>" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" style="cursor: pointer;">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold fs-4 text-light text-truncate" style="max-width: 70%;"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></span>
                    <span class="badge bg-purple font-monospace px-3 py-2 fs-6 rounded shadow-sm"><?= count($demarches) ?> action(s)</span>
                </div>

                <div class="mb-3">
                    <div class="text-muted-custom small text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">État Actuel Dossier</div>
                    <span class="badge <?= $card_badge_class ?> font-monospace px-3 py-2 fs-5 w-100 text-center shadow-sm fw-bold">
                        <?= htmlspecialchars($status_text) ?>
                    </span>
                </div>

                <div class="row g-2 text-muted-custom border-top border-secondary pt-2" style="font-size: 0.95rem;">
                    <div class="col-12 mb-1 text-truncate text-white fw-semibold">
                        <i class="bi bi-envelope text-purple me-2"></i><?= htmlspecialchars($e['email']) ?>
                    </div>
                    <div class="col-6"><i class="bi bi-collection text-secondary me-1"></i> TD : <strong class="text-light fs-6"><?= htmlspecialchars($e['gp_td']) ?></strong></div>
                    <div class="col-6"><i class="bi bi-people text-secondary me-1"></i> TP : <strong class="text-light fs-6"><?= htmlspecialchars($e['gp_tp']) ?></strong></div>
                    
                    <?php if ($est_responsable_mmi1 && $e['statut_actuel'] === 'En attente'): ?>
                        <div class="col-12 mt-2"><button class="btn btn-purple btn-sm w-100 font-monospace py-1" data-bs-toggle="modal" data-bs-target="#modalAffecter" onclick="document.getElementById('select_etudiant').value = '<?= $e['id_etudiant'] ?>'"><i class="bi bi-file-earmark-check me-1"></i> Affecter & Créer Convention</button></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="<?= $collapseId ?>" class="collapse" data-bs-parent="#accordionSuivi_<?= $promoIdClean ?>">
                <div class="p-3 bg-dark border-top border-secondary">
                    
                    <?php if ($stage_data): ?>
                        <h6 class="text-success fw-bold mb-2"><i class="bi bi-shield-check me-2"></i>Suivi de la Convention Active — <?= htmlspecialchars($stage_data['nom_societe']) ?></h6>
                        <form method="POST" action="" class="bg-intranet-dark p-3 rounded border border-secondary mb-4">
                            <input type="hidden" name="id_stage" value="<?= $stage_data['id_stage'] ?>">
                            <div class="row g-2 align-items-end small">
                                <div class="col-md-5">
                                    <label class="form-label text-muted-custom m-0 mb-1">Date de visite tuteur</label>
                                    <input type="date" name="date_visite" class="form-control form-control-sm bg-dark text-white border-secondary" value="<?= $stage_data['date_visite']; ?>" <?= !$est_responsable_mmi1 ? 'readonly' : ''; ?>>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted-custom m-0 mb-1">Alerte / Statut Stage</label>
                                    <?php if ($est_responsable_mmi1): ?>
                                        <select name="etat_validation" class="form-select form-select-sm bg-dark text-white border-secondary">
                                            <option value="En cours" <?= ($stage_data['etat_validation'] === 'En cours') ? 'selected' : ''; ?>>En cours</option>
                                            <option value="Validé" <?= ($stage_data['etat_validation'] === 'Validé') ? 'selected' : ''; ?>>Validé (Terminé)</option>
                                            <option value="Problème" <?= ($stage_data['etat_validation'] === 'Problème') ? 'selected' : ''; ?>>Signaler un problème</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary opacity-50" value="<?= $stage_data['etat_validation']; ?>" readonly>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <?php if ($est_responsable_mmi1): ?>
                                        <button type="submit" name="action_update_suivi" class="btn btn-purple btn-sm py-1"><i class="bi bi-save"></i> Sauver</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1" disabled><i class="bi bi-lock"></i> Lock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <h5 class="text-purple fw-bold mb-3 border-bottom border-secondary pb-1" style="font-size: 1rem;"><i class="bi bi-journal-text me-2"></i>Historique des candidatures</h5>
                    <?php if (empty($demarches)): ?>
                        <div class="text-center py-2 text-muted-custom small"><i class="bi bi-info-circle me-1"></i> Aucune démarche déclarée.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($demarches as $d): 
                                $b_class = ($d['reponse'] === 'Validé' || $d['reponse'] === 'Convention signée') ? "bg-success text-white" : (($d['reponse'] === 'Refusé') ? "bg-danger text-white" : "bg-warning text-dark");
                            ?>
                                <div class="p-2 rounded bg-intranet-dark border border-secondary shadow-sm" style="font-size: 0.85rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-info font-monospace fw-bold"><i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y', strtotime($d['date_contact'])) ?></span>
                                        <span class="badge <?= $b_class ?> font-monospace px-2 py-0.5"><?= htmlspecialchars($d['reponse']) ?></span>
                                    </div>
                                    <div class="text-white-50"><i class="bi bi-building me-1 text-secondary"></i><?= htmlspecialchars($d['entreprise_cible']) ?> — <span class="text-light"><?= htmlspecialchars($d['type_action']) ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des Stages - Enseignant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="suivi-stages.php" style="font-size: 0.85rem;">
                            <i class="bi bi-person-video3 me-2 fs-6"></i> Suivi des Stages
                        </a>
                    </li>
                    
                    <?php if ($est_un_responsable): ?>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="validation-stages.php" style="font-size: 0.85rem;">
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

    <main class="container-fluid px-4 py-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-purple"><i class="bi bi-people-fill me-2"></i>Suivi des démarches et stages par promotion</h2>
            <div>
                <?php if ($est_responsable_mmi1): ?>
                    <button class="btn btn-purple btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalOffre"><i class="bi bi-plus-circle me-1"></i> Saisir une Offre</button>
                    <button class="btn btn-outline-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalAffecter"><i class="bi bi-person-plus me-1"></i> Affecter Étudiant</button>
                    <button class="btn btn-outline-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalRecherche"><i class="bi bi-search me-1"></i> Saisir Recherche</button>
                    <button class="btn btn-outline-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalOral"><i class="bi bi-calendar-plus me-1"></i> Organiser Oral</button>
                    <button onclick="window.print();" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-up me-1"></i> Remontée Rapport</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-md-3"><div class="card-custom p-3 border-secondary"><div class="small fw-bold text-muted-custom">EFFECTIF PROMO (MMI1)</div><h3 class="fw-bold m-0 mt-1"><?php echo $total_mmi1; ?> étudiants</h3></div></div>
            <div class="col-md-3"><div class="card-custom p-3 border-success"><div class="small fw-bold text-success">STAGES VALIDÉS (MMI1)</div><h3 class="fw-bold m-0 mt-1 text-success"><?php echo $total_valide; ?> affectés</h3></div></div>
            <div class="col-md-3"><div class="card-custom p-3 border-warning"><div class="small fw-bold text-warning">RECHERCHES EN COURS (MMI1)</div><h3 class="fw-bold m-0 mt-1 text-warning"><?php echo $total_en_recherche; ?> actifs</h3></div></div>
            <div class="col-md-3"><div class="card-custom p-3" style="border: 2px solid #ff0055;"><div class="small fw-bold" style="color: #ff0055;">ALERTES / PROBLÈMES TERRAIN</div><h3 class="fw-bold m-0 mt-1" style="color: #ff0055;"><?php echo $total_alertes; ?> urgences</h3></div></div>
        </div>

        <div class="card-custom p-3 mb-4 bg-intranet-dark border-secondary">
            <form method="GET" action="suivi-stages.php" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select bg-dark text-white border-secondary fw-bold" id="search_type" name="search_type" onchange="toggleSearchInputs()">
                        <option value="prenom" <?php echo $search_type === 'prenom' ? 'selected' : ''; ?>>Rechercher par Prénom</option>
                        <option value="matricule" <?php echo $search_type === 'matricule' ? 'selected' : ''; ?>>Rechercher par Matricule</option>
                        <option value="statut" <?php echo $search_type === 'statut' ? 'selected' : ''; ?>>Filtrer par Statut historique</option>
                    </select>
                </div>
                <div class="col-md-4" id="wrapper_text_input">
                    <input type="text" class="form-control bg-dark text-white border-secondary" id="search_query" name="search_query" placeholder="Tapez votre recherche..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-4" id="wrapper_status_select" style="display: none;">
                    <select class="form-select bg-dark text-white border-secondary" id="search_status" name="search_status">
                        <option value="En attente" <?php echo $search_status === 'En attente' ? 'selected' : ''; ?>>⏳ En attente de réponse</option>
                        <option value="Entretien prévu" <?php echo $search_status === 'Entretien prévu' ? 'selected' : ''; ?>>📅 Entretien prévu</option>
                        <option value="Refusé" <?php echo $search_status === 'Refusé' ? 'selected' : ''; ?>>❌ Refusé</option>
                        <option value="Validé" <?php echo $search_status === 'Validé' ? 'selected' : ''; ?>>✅ Validé / Convention signée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select bg-dark text-white border-secondary fw-bold text-info" id="search_promo" name="search_promo">
                        <option value="toutes" <?php echo $search_promo === 'toutes' ? 'selected' : ''; ?>>🎓 Toutes les promotions</option>
                        <option value="MMI 1" <?php echo $search_promo === 'MMI 1' ? 'selected' : ''; ?>>Promo : MMI 1</option>
                        <option value="MMI 2" <?php echo $search_promo === 'MMI 2' ? 'selected' : ''; ?>>Promo : MMI 2</option>
                        <option value="MMI 3" <?php echo $search_promo === 'MMI 3' ? 'selected' : ''; ?>>Promo : MMI 3</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button type="submit" class="btn btn-purple fw-bold">Filtrer</button></div>
            </form>
        </div>

        <div class="row g-4">
            <?php if ($search_promo === 'toutes' || $search_promo === 'MMI 1'): ?>
                <div class="<?= $column_class ?>">
                    <div class="p-3 card-custom h-100" style="background-color: rgba(0,0,0,0.15); border-top: 4px solid #8a2be2;">
                        <h4 class="fw-bold text-info mb-3 border-bottom border-secondary pb-2"><i class="bi bi-mortarboard me-2"></i>MMI 1</h4>
                        <?php if (empty($mmi1)): ?>
                            <p class="text-muted small ps-1">Aucun dossier étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI1"><?php afficher_liste_suivi_responsable($mmi1, $pdo, $est_responsable_mmi1); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($search_promo === 'toutes' || $search_promo === 'MMI 2'): ?>
                <div class="<?= $column_class ?>">
                    <div class="p-3 card-custom h-100" style="background-color: rgba(0,0,0,0.15); border-top: 4px solid #b23b8c;">
                        <h4 class="fw-bold text-info mb-3 border-bottom border-secondary pb-2"><i class="bi bi-mortarboard me-2"></i>MMI 2</h4>
                        <?php if (empty($mmi2)): ?>
                            <p class="text-muted small ps-1">Aucun dossier étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI2"><?php afficher_liste_suivi_responsable($mmi2, $pdo, $est_responsable_mmi1); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($search_promo === 'toutes' || $search_promo === 'MMI 3'): ?>
                <div class="<?= $column_class ?>">
                    <div class="p-3 card-custom h-100" style="background-color: rgba(0,0,0,0.15); border-top: 4px solid #3bb273;">
                        <h4 class="fw-bold text-info mb-3 border-bottom border-secondary pb-2"><i class="bi bi-mortarboard me-2"></i>MMI 3</h4>
                        <?php if (empty($mmi3)): ?>
                            <p class="text-muted small ps-1">Aucun dossier étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI3"><?php afficher_liste_suivi_responsable($mmi3, $pdo, $est_responsable_mmi1); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($est_responsable_mmi1): ?>
    <div class="modal fade" id="modalOffre" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                <div class="modal-header bg-intranet-dark border-secondary"><h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-purple me-2"></i>Saisir une nouvelle offre</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small">Intitulé du poste</label><input type="text" name="intitule" class="form-control bg-dark text-white border-secondary" required></div>
                    <div class="mb-3"><label class="form-label small">Description des missions</label><textarea name="description" rows="3" class="form-control bg-dark text-white border-secondary" required></textarea></div>
                    <div class="mb-3"><label class="form-label small">Compétences requises</label><input type="text" name="competences" class="form-control bg-dark text-white border-secondary" placeholder="Ex: HTML, Figma, PHP"></div>
                    <div class="row g-2"><div class="col-6"><label class="form-label small">Durée</label><input type="text" name="duree" placeholder="Ex: 8 semaines" class="form-control bg-dark text-white border-secondary"></div><div class="col-6"><label class="form-label small">Lieu</label><input type="text" name="lieu" class="form-control bg-dark text-white border-secondary"></div></div>
                    <div class="mt-3"><label class="form-label small">Gratification (€ / mois)</label><input type="number" step="0.01" name="remuneration" class="form-control bg-dark text-white border-secondary"></div>
                </div>
                <div class="modal-footer border-secondary"><button type="submit" name="action_creer_offre" class="btn btn-purple btn-sm">Publier l'offre</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalAffecter" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                <div class="modal-header bg-intranet-dark border-secondary"><h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-purple me-2"></i>Affectation & Convention</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small">Sélectionner l'étudiant</label><select name="id_etudiant" id="select_etudiant" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_etudiants_mmi1 as $et): ?><option value="<?php echo $et['id_etudiant']; ?>"><?php echo htmlspecialchars($et['nom']." ".$et['prenom']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="mb-3"><label class="form-label small">Entreprise d'accueil</label><select name="id_entreprise" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_entreprises as $ent): ?><option value="<?php echo $ent['id_entreprise']; ?>"><?php echo htmlspecialchars($ent['nom_societe']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="mb-3"><label class="form-label small">Maître de stage</label><select name="id_responsable" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_tuteurs_pro as $tpro): ?><option value="<?php echo $tpro['id_responsable']; ?>"><?php echo htmlspecialchars($tpro['nom']." ".$tpro['prenom']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="row g-2 mb-3"><div class="col-6"><label class="form-label small">N° Convention</label><input type="text" name="num_convention" class="form-control bg-dark text-white border-secondary" required></div><div class="col-6"><label class="form-label small">Sujet du stage</label><input type="text" name="sujet_stage" class="form-control bg-dark text-white border-secondary" required></div></div>
                    <div class="row g-2"><div class="col-6"><label class="form-label small">Date de début</label><input type="date" name="date_deb" class="form-control bg-dark text-white border-secondary" required></div><div class="col-6"><label class="form-label small">Date de fin</label><input type="date" name="date_fin" class="form-control bg-dark text-white border-secondary" required></div></div>
                </div>
                <div class="modal-footer border-secondary"><button type="submit" name="action_affecter_etudiant" class="btn btn-purple btn-sm">Valider l'affectation</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalRecherche" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                <div class="modal-header bg-intranet-dark border-secondary"><h5 class="modal-title fw-bold"><i class="bi bi-search text-purple me-2"></i>Saisir un avancement</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small">Étudiant</label><select name="id_etudiant" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_etudiants_mmi1 as $et): ?><option value="<?php echo $et['id_etudiant']; ?>"><?php echo htmlspecialchars($et['nom']." ".$et['prenom']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="mb-3"><label class="form-label small">Entreprise contactée</label><input type="text" name="entreprise_cible" class="form-control bg-dark text-white border-secondary" required placeholder="Ex: Ubisoft"></div>
                    <div class="mb-3"><label class="form-label small">Type de démarche</label><select name="type_action" class="form-select bg-dark text-white border-secondary"><option value="Candidature spontanée">Candidature spontanée</option><option value="Envoi de CV">Envoi de CV (Réponse à offre)</option><option value="Relance téléphonique">Relance téléphonique</option></select></div>
                    <div class="mb-3"><label class="form-label small">État actuel</label><select name="reponse" class="form-select bg-dark text-white border-secondary"><option value="En attente">En attente</option><option value="Refusé">Refusé</option><option value="Entretien prévu">Entretien prévu</option><option value="Accepté">Accepté</option></select></div>
                </div>
                <div class="modal-footer border-secondary"><button type="submit" name="action_saisir_recherche" class="btn btn-purple btn-sm">Enregistrer l'historique</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalOral" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                <div class="modal-header bg-intranet-dark border-secondary"><h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus text-purple me-2"></i>Planifier un oral d'examen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small">Candidat (Étudiant)</label><select name="id_etudiant" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_etudiants_mmi1 as $et): ?><option value="<?php echo $et['id_etudiant']; ?>"><?php echo htmlspecialchars($et['nom']." ".$et['prenom']); ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="row g-2 mb-3"><div class="col-4"><label class="form-label small">Date</label><input type="date" name="date_soutenance" class="form-control bg-dark text-white border-secondary" required></div><div class="col-4"><label class="form-label small">Horaire</label><input type="time" name="heure_soutenance" class="form-control bg-dark text-white border-secondary" required></div><div class="col-4"><label class="form-label small">Salle</label><input type="text" name="salle" placeholder="Ex: IUT-102" class="form-control bg-dark text-white border-secondary" required></div></div>
                    <div class="row g-2"><div class="col-6"><label class="form-label small">Membre Jury 1</label><select name="jury1" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_profs as $p): ?><option value="<?php echo $p['id_enseignant']; ?>">M./Mme <?php echo htmlspecialchars($p['nom']); ?></option><?php endforeach; ?>
                    </select></div><div class="col-6"><label class="form-label small">Membre Jury 2</label><select name="jury2" class="form-select bg-dark text-white border-secondary">
                        <?php foreach($liste_profs as $p): ?><option value="<?php echo $p['id_enseignant']; ?>">M./Mme <?php echo htmlspecialchars($p['nom']); ?></option><?php endforeach; ?>
                    </select></div></div>
                </div>
                <div class="modal-footer border-secondary"><button type="submit" name="action_planifier_oral" class="btn btn-purple btn-sm">Planifier la soutenance</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

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