<?php
session_start();

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
$nom_etudiant = $_SESSION['user']['nom'] ?? 'Nom';
$prenom_etudiant = $_SESSION['user']['prenom'] ?? 'Prénom';

$stmtPromoEt = $pdo->prepare("SELECT promo FROM etudiant WHERE id_etudiant = ?");
$stmtPromoEt->execute([$id_etudiant]);
$promo_brute = $stmtPromoEt->fetchColumn() ?? 'MMI 1';
$promo_filtre = str_replace(' ', '', $promo_brute);

// Calcul du statut global
$statut_global = "recherche";
$stmtDernier = $pdo->prepare("SELECT reponse FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC LIMIT 1");
$stmtDernier->execute([$id_etudiant]);
$derniere_action = $stmtDernier->fetch(PDO::FETCH_ASSOC);

if ($derniere_action) {
    if ($derniere_action['reponse'] === 'Validé' || $derniere_action['reponse'] === 'Convention signée') {
        $statut_global = "convention";
    } elseif ($derniere_action['reponse'] === 'Entretien' || $derniere_action['reponse'] === 'Entretien prévu') {
        $statut_global = "entretien";
    } elseif ($derniere_action['reponse'] === 'En attente' || $derniere_action['reponse'] === 'Refusé') {
        $statut_global = "recherche";
    }
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM historique WHERE id_etudiant = ?");
$stmtTotal->execute([$id_etudiant]);
$total_postules = $stmtTotal->fetchColumn();

$stmtAttente = $pdo->prepare("SELECT COUNT(*) FROM historique WHERE id_etudiant = ? AND reponse = 'En attente'");
$stmtAttente->execute([$id_etudiant]);
$total_en_attente = $stmtAttente->fetchColumn();

$stmtSoutenance = $pdo->prepare("
    SELECT s.*, ens1.nom AS j1_nom, ens1.prenom AS j1_prenom, ens2.nom AS j2_nom, ens2.prenom AS j2_prenom
    FROM soutenance s
    LEFT JOIN enseignant ens1 ON s.id_enseignant_1 = ens1.id_enseignant
    LEFT JOIN enseignant ens2 ON s.id_enseignant_2 = ens2.id_enseignant
    WHERE s.id_etudiant = ?
");
$stmtSoutenance->execute([$id_etudiant]);
$soutenance = $stmtSoutenance->fetch(PDO::FETCH_ASSOC);

$a_une_soutenance = ($soutenance ? true : false);
$notes_disponibles = ($a_une_soutenance && $soutenance['note_rapport'] !== null && $soutenance['note_oral'] !== null);

$stmtDernieresOffres = $pdo->prepare("SELECT * FROM offre WHERE promotion_visee = ? ORDER BY id_offre DESC LIMIT 3");
$stmtDernieresOffres->execute([$promo_filtre]);
$dernieres_offres = $stmtDernieresOffres->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Étudiant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
    <script>
        const themeEnregistre = localStorage.getItem('intranet-theme') || 'light';
        if (themeEnregistre === 'dark') { document.documentElement.classList.add('dark-theme-init'); }
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
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="accueil-etudiant.php">
                <img src="../images/logo-noir-blanc.png" alt="Logo" class="me-3" style="height: 50px; width: auto;"> 
                <div class="lh-sm">
                    <div class="fw-bold text-uppercase" style="font-size: 1.1rem; letter-spacing: 1px;">GESTIONNAIRE DE STAGE</div>
                    <div class="text-muted-custom" style="font-size: 0.8rem;">UNIVERSITÉ GUSTAVE EIFFEL</div>
                </div>
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <li class="nav-item"><a class="nav-link nav-link-custom active d-flex align-items-center" href="accueil-etudiant.php"><i class="bi bi-house-door me-2 fs-4"></i> Accueil</a></li>
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom d-flex align-items-center" href="demarches-etudiant.php"><i class="bi bi-folder me-2 fs-4"></i> Démarches</a></li>
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom d-flex align-items-center" href="offres-etudiant.php"><i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Offres</a></li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <!-- BOUTON DU COMMUTATEUR -->
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

    <main class="container-fluid px-4 py-4">
        <div class="row g-3 mb-4 justify-content-center text-center">
            <div class="col-md-2">
                <div class="card-custom p-3 stepper-recherche <?= ($statut_global === 'recherche') ? 'stepper-active' : 'stepper-disabled' ?>">
                    <i class="bi bi-search mb-2 fs-3 text-info"></i>
                    <div class="small fw-bold">En recherche</div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center"><i class="bi bi-chevron-right text-secondary"></i></div>
            <div class="col-md-2">
                <div class="card-custom p-3 stepper-entretien <?= ($statut_global === 'entretien') ? 'stepper-active' : 'stepper-disabled' ?>">
                    <i class="bi bi-chat-dots mb-2 fs-3 text-primary"></i>
                    <div class="small fw-bold">En entretien</div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center"><i class="bi bi-chevron-right text-secondary"></i></div>
            <div class="col-md-2">
                <div class="card-custom p-3 stepper-convention <?= ($statut_global === 'convention') ? 'stepper-active' : 'stepper-disabled' ?>">
                    <i class="bi bi-file-earmark-check mb-2 fs-3 text-success"></i>
                    <div class="small fw-bold">Convention signée</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <h5 class="mb-3 fw-bold">Nouvelles offres — <?= htmlspecialchars($promo_brute) ?></h5>
                <?php if (count($dernieres_offres) === 0): ?>
                    <div class="card-custom p-3 text-center text-muted-custom small">Aucune offre récente.</div>
                <?php else: ?>
                    <?php foreach ($dernieres_offres as $off): ?>
                        <div class="card-custom p-3 mb-3" style="cursor: pointer; border: 2px solid #8a2be2; box-shadow: 0 0 12px rgba(138, 43, 226, 0.2);" onclick="window.location.href='offres-etudiant.php';">
                            <div class="text-purple small fw-bold mb-1"><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($off['lieu'] ?? 'Non spécifié') ?></div>
                            <div class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($off['intitule']) ?></div>
                            <p class="small text-white-50 mb-2 text-truncate"><?= htmlspecialchars($off['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-6">
                <div class="card-custom overflow-hidden mb-4 position-relative" style="height: 350px;">
                    <img src="../images/IUT-MEAUX.jpg" class="w-100 h-100 object-fit-cover opacity-50" alt="Campus">
                    <div class="position-absolute bottom-0 start-0 p-4 w-100 bg-gradient-dark">
                        <h2 class="fw-bold">Bienvenue, <?= htmlspecialchars($prenom_etudiant) ?> !</h2>
                        <p>Suivez l'avancement de vos démarches et consultez les offres exclusives.</p>
                        <button onclick="window.location.href='demarches-etudiant.php';" class="btn btn-purple mt-2">Voir mes démarches <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>
                </div>
                <div class="card-custom p-3">
                    <h6 class="fw-bold"><i class="bi bi-play-circle me-2"></i> Tuto : Remplir sa convention</h6>
                    <div class="ratio ratio-16x9 mt-3">
                        <iframe src="https://www.youtube.com/embed/0_TJUsmGc9U?si=FPfOYxyL5iXIKkrZ" title="Tuto" style="border-radius: 8px;"></iframe>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <h5 class="mb-3 fw-bold">Mes démarches</h5>
                <div class="card-custom p-4 text-center mb-4">
                    <div class="text-start">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span>Offres postulées</span>
                            <span class="badge bg-primary rounded-pill"><?= (int)$total_postules ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span>Réponses en attente</span>
                            <span class="badge bg-warning text-dark rounded-pill"><?= (int)$total_en_attente ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($a_une_soutenance): ?>
                    <h5 class="mb-3 fw-bold"><i class="bi bi-calendar3 me-2 text-info"></i>Ma Soutenance</h5>
                    <div class="card-custom p-3 border-info mb-4">
                        <div class="small mb-1"><span class="text-muted-custom">Date :</span> <strong><?= date('d/m/Y', strtotime($soutenance['date'])) ?></strong></div>
                        <div class="small mb-1"><span class="text-muted-custom">Heure :</span> <strong><?= substr($soutenance['heure'], 0, 5) ?></strong></div>
                        <div class="small mb-2"><span class="text-muted-custom">Salle :</span> <span class="badge bg-secondary"><?= htmlspecialchars($soutenance['salle']) ?></span></div>
                        <div class="border-top border-secondary pt-2 mt-2">
                            <div class="text-muted-custom small mb-1">Jury :</div>
                            <div class="small"><i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($soutenance['j1_prenom'] . ' ' . $soutenance['j1_nom']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($notes_disponibles): ?>
                    <h5 class="mb-3 fw-bold"><i class="bi bi-journal-check me-2 text-success"></i>Mes Notes</h5>
                    <div class="card-custom p-3 border-success mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>Rapport :</span>
                            <span class="fw-bold"><?= number_format($soutenance['note_rapport'], 2, ',', ' ') ?> / 20</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>Oral :</span>
                            <span class="fw-bold"><?= number_format($soutenance['note_oral'], 2, ',', ' ') ?> / 20</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4"><p class="m-0 text-muted-custom" style="font-size: 0.85rem;">&copy; 2026 Université Gustave Eiffel</p></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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