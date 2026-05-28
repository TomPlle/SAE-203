<?php
session_start();

// 1. Sécurité : Vérifier si l'utilisateur est connecté et est enseignant
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

$msg_success = "";
$msg_error = "";

// --- Traitement de la Planification d'une nouvelle soutenance ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_planifier'])) {
    $id_etudiant = $_POST['id_etudiant'];
    $date_sout = $_POST['date_soutenance'];
    $heure_sout = $_POST['heure_soutenance'];
    $salle = $_POST['salle'];
    $jurys_selectionnes = isset($_POST['jurys']) ? $_POST['jurys'] : [];

    if (count($jurys_selectionnes) !== 2) {
        $msg_error = "Erreur : Vous devez cocher exactement 2 enseignants pour le jury.";
    } else {
        $id_jury1 = $jurys_selectionnes[0];
        $id_jury2 = $jurys_selectionnes[1];

        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO soutenance (date, heure, salle, id_etudiant, id_enseignant_1, id_enseignant_2) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$date_sout, $heure_sout, $salle, $id_etudiant, $id_jury1, $id_jury2]);
            $msg_success = "La soutenance a été planifiée avec succès !";
        } catch (PDOException $e) {
            $msg_error = "Erreur lors de l'autorisation ou de la planification : " . $e->getMessage();
        }
    }
}

// Traitement de la saisie des notes par le jury
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_notes'])) {
    $id_soutenance = $_POST['id_soutenance'];
    $note_rapport = !empty($_POST['note_rapport']) ? str_replace(',', '.', $_POST['note_rapport']) : null;
    $note_oral = !empty($_POST['note_oral']) ? str_replace(',', '.', $_POST['note_oral']) : null;

    // SÉCURITÉ DOUBLE VERIFICATION
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM soutenance WHERE id_soutenance = ? AND (id_enseignant_1 = ? OR id_enseignant_2 = ?)");
    $stmtCheck->execute([$id_soutenance, $id_enseignant, $id_enseignant]);
    
    if ($stmtCheck->fetchColumn() == 0) {
        $msg_error = "Erreur de sécurité : Vous ne faites pas partie du jury de cette soutenance. Saisie refusée.";
    } else {
        $stmtNotes = $pdo->prepare("UPDATE soutenance SET note_rapport = ?, note_oral = ? WHERE id_soutenance = ?");
        $stmtNotes->execute([$note_rapport, $note_oral, $id_soutenance]);
        $msg_success = "Notes enregistrées avec succès ! Elles sont désormais consultables par l'étudiant.";
    }
}

// Récupération de toutes les soutenances de l'établissement
$stmtSout = $pdo->prepare("
    SELECT s.*, et.nom AS et_nom, et.prenom AS et_prenom, et.promo,
           ens1.nom AS ens1_nom, ens1.id_enseignant AS ens1_id,
           ens2.nom AS ens2_nom, ens2.id_enseignant AS ens2_id
    FROM soutenance s
    JOIN etudiant et ON s.id_etudiant = et.id_etudiant
    JOIN enseignant ens1 ON s.id_enseignant_1 = ens1.id_enseignant
    JOIN enseignant ens2 ON s.id_enseignant_2 = ens2.id_enseignant
    ORDER BY s.date ASC, s.heure ASC
");
$stmtSout->execute();
$soutenances = $stmtSout->fetchAll(PDO::FETCH_ASSOC);

// Liste des étudiants sans soutenance
$liste_etudiants = $pdo->query("SELECT id_etudiant, nom, prenom, promo FROM etudiant WHERE id_etudiant NOT IN (SELECT id_etudiant FROM soutenance) ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
// Liste de tous les enseignants disponibles pour le jury
$liste_enseignants = $pdo->query("SELECT id_enseignant, nom, prenom FROM enseignant ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Soutenances & Notes - Enseignant</title> 
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
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-stages.php" style="font-size: 0.85rem;">
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="soutenances-enseignant.php" style="font-size: 0.85rem;">
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

    <main class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0"><i class="bi bi-calendar-event me-2 text-success"></i> Évaluation des Soutenances</h3>
            <div>
                <button type="button" class="btn btn-purple btn-sm me-3" data-bs-toggle="modal" data-bs-target="#modalPlanifier">
                    <i class="bi bi-calendar-plus me-2"></i> Planifier une soutenance
                </button>
                <span class="badge border-warning text-warning p-2"><i class="bi bi-exclamation-triangle me-2"></i> Rappel : Saisie sous 7 jours maximum</span>
            </div>
        </div>

        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4"><?php echo $msg_success; ?></div>
        <?php endif; ?>

        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 mb-4"><i class="bi bi-exclamation-octagon me-2"></i> <?php echo $msg_error; ?></div>
        <?php endif; ?>

        <div class="card-custom p-0 overflow-hidden border-secondary">
            <div class="table-responsive">
                <table class="table table-dark table-hover m-0 align-middle">
                    <thead class="bg-intranet-dark">
                        <tr class="text-muted-custom border-secondary small">
                            <th>Session</th>
                            <th>Salle</th>
                            <th>Étudiant (Promo)</th>
                            <th>Composition Jury</th>
                            <th style="width: 320px;">Saisie des Notes (/20)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($soutenances) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune soutenance n'est programmée à votre planning.</td></tr>
                        <?php else: ?>
                            <?php foreach ($soutenances as $sout): ?>
                                <?php 
                                    $est_jury_de_ce_candidat = ($id_enseignant == $sout['ens1_id'] || $id_enseignant == $sout['ens2_id']);
                                ?>
                                <tr class="border-secondary">
                                    <td>
                                        <div class="fw-bold text-white"><?php echo date('d/m/Y', strtotime($sout['date'])); ?></div>
                                        <div class="small text-muted-custom"><i class="bi bi-clock me-1"></i> <?php echo substr($sout['heure'], 0, 5); ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($sout['salle']); ?></span></td>
                                    <td>
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($sout['et_prenom'] . ' ' . $sout['et_nom']); ?></div>
                                        <div class="small text-info"><?php echo htmlspecialchars($sout['promo']); ?></div>
                                    </td>
                                    <td class="small">
                                        <div class="text-muted-custom">Jury 1 : <span class="<?php echo ($id_enseignant == $sout['ens1_id']) ? 'text-success fw-bold' : 'text-white'; ?>"><?php echo htmlspecialchars($sout['ens1_nom']); ?></span></div>
                                        <div class="text-muted-custom">Jury 2 : <span class="<?php echo ($id_enseignant == $sout['ens2_id']) ? 'text-success fw-bold' : 'text-white'; ?>"><?php echo htmlspecialchars($sout['ens2_nom']); ?></span></div>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="row g-2 align-items-center">
                                            <input type="hidden" name="id_soutenance" value="<?php echo $sout['id_soutenance']; ?>">
                                            
                                            <div class="col-4">
                                                <input type="number" step="0.01" min="0" max="20" name="note_rapport" 
                                                       class="form-control form-control-sm bg-dark text-white border-secondary text-center <?php echo !$est_jury_de_ce_candidat ? 'opacity-50' : ''; ?>" 
                                                       placeholder="Rapport" value="<?php echo $sout['note_rapport']; ?>" 
                                                       required <?php echo !$est_jury_de_ce_candidat ? 'readonly' : ''; ?>>
                                            </div>
                                            
                                            <div class="col-4">
                                                <input type="number" step="0.01" min="0" max="20" name="note_oral" 
                                                       class="form-control form-control-sm bg-dark text-white border-secondary text-center <?php echo !$est_jury_de_ce_candidat ? 'opacity-50' : ''; ?>" 
                                                       placeholder="Oral" value="<?php echo $sout['note_oral']; ?>" 
                                                       required <?php echo !$est_jury_de_ce_candidat ? 'readonly' : ''; ?>>
                                            </div>
                                            
                                            <div class="col-4 d-grid">
                                                <?php if ($est_jury_de_ce_candidat): ?>
                                                    <button type="submit" name="action_notes" class="btn btn-success btn-sm" title="Enregistrer les notes"><i class="bi bi-save"></i></button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm opacity-50" title="Lecture seule (Vous ne faites pas partie du jury)" disabled><i class="bi bi-lock-fill"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalPlanifier" tabindex="-1" aria-labelledby="modalPlanifierLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header bg-intranet-dark border-secondary">
                    <h5 class="modal-title fw-bold" id="modalPlanifierLabel"><i class="bi bi-calendar-plus text-purple me-2"></i> Nouvelle planification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted-custom">Sélectionner l'étudiant</label>
                            <select name="id_etudiant" class="form-select bg-black text-white border-secondary" required>
                                <option value="" disabled selected>-- Choisir un étudiant --</option>
                                <?php foreach ($liste_etudiants as $et): ?>
                                    <option value="<?php echo $et['id_etudiant']; ?>"><?php echo htmlspecialchars(strtoupper($et['nom']) . ' ' . $et['prenom'] . ' (' . $et['promo'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted-custom">Date de passage</label>
                                <input type="date" name="date_soutenance" class="form-control bg-black text-white border-secondary" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted-custom">Heure</label>
                                <input type="time" name="heure_soutenance" class="form-control bg-black text-white border-secondary" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted-custom">Salle</label>
                                <input type="text" name="salle" placeholder="Ex: A104" class="form-control bg-black text-white border-secondary" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted-custom">Composer le jury (Cochez exactement 2 participants)</label>
                            <div class="bg-black rounded border border-secondary p-3" style="max-height: 160px; overflow-y: auto;">
                                <div class="row g-2">
                                    <?php foreach ($liste_enseignants as $ens): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input check-jury" type="checkbox" name="jurys[]" value="<?php echo $ens['id_enseignant']; ?>" id="jury_<?php echo $ens['id_enseignant']; ?>" onchange="verifierCompteJury(this)">
                                                <label class="form-check-label text-white-50" for="jury_<?php echo $ens['id_enseignant']; ?>">
                                                    M./Mme <?php echo htmlspecialchars($ens['nom'] . ' ' . $ens['prenom']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-intranet-dark border-secondary">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action_planifier" class="btn btn-purple btn-sm">Enregistrer la session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4">
            <p class="m-0 text-muted-custom" style="font-size: 0.85rem;">&copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel</p>
        </div>
    </footer>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verifierCompteJury(cb) {
    var cochees = document.querySelectorAll('.check-jury:checked');
    if (cochees.length > 2) {
        alert("Une soutenance nécessite strictement 2 membres de jury.");
        cb.checked = false;
    }
}
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
</html>