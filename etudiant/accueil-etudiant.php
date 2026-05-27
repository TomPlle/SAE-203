<?php
session_start();

// 1. Sécurité : Vérifier si l'étudiant est bien connecté
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

// Récupération des informations de l'étudiant connecté depuis la session
$id_etudiant = $_SESSION['user']['id_etudiant']; 
$nom_etudiant = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : 'Nom';
$prenom_etudiant = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : 'Prénom';

// Récupération de la promo brute pour le filtrage des offres (ex: "MMI 1", "MMI 2")
$stmtPromoEt = $pdo->prepare("SELECT promo FROM etudiant WHERE id_etudiant = ?");
$stmtPromoEt->execute([$id_etudiant]);
$promo_brute = $stmtPromoEt->fetchColumn() ?? 'MMI 1';
$promo_filtre = str_replace(' ', '', $promo_brute); // Transforme "MMI 2" en "MMI2" pour correspondre à l'ENUM

// 2. Statut global (Stepper) basé sur la dernière action dans l'historique
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

// 3. STATISTIQUES DES DÉMARCHES
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM historique WHERE id_etudiant = ?");
$stmtTotal->execute([$id_etudiant]);
$total_postules = $stmtTotal->fetchColumn();

$stmtAttente = $pdo->prepare("SELECT COUNT(*) FROM historique WHERE id_etudiant = ? AND reponse = 'En attente'");
$stmtAttente->execute([$id_etudiant]);
$total_en_attente = $stmtAttente->fetchColumn();

// 4. RÉCUPÉRATION DE LA SOUTENANCE ET DES NOTES
$stmtSoutenance = $pdo->prepare("
    SELECT s.*, 
           ens1.nom AS j1_nom, ens1.prenom AS j1_prenom,
           ens2.nom AS j2_nom, ens2.prenom AS j2_prenom
    FROM soutenance s
    LEFT JOIN enseignant ens1 ON s.id_enseignant_1 = ens1.id_enseignant
    LEFT JOIN enseignant ens2 ON s.id_enseignant_2 = ens2.id_enseignant
    WHERE s.id_etudiant = ?
");
$stmtSoutenance->execute([$id_etudiant]);
$soutenance = $stmtSoutenance->fetch(PDO::FETCH_ASSOC);

$a_une_soutenance = ($soutenance ? true : false);
$notes_disponibles = ($a_une_soutenance && $soutenance['note_rapport'] !== null && $soutenance['note_oral'] !== null);

// 5. RÉCUPÉRATION DES 3 DERNIÈRES OFFRES DISPONIBLES
$stmtDernieresOffres = $pdo->prepare("SELECT * FROM offre WHERE promotion_visee = ? ORDER BY id_offre DESC LIMIT 3");
$stmtDernieresOffres->execute([$promo_filtre]);
$dernieres_offres = $stmtDernieresOffres->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Étudiant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="accueil-etudiant.php">
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
                <div class="card-custom p-3 <?php echo ($statut_global === 'recherche') ? 'border-info' : 'opacity-50'; ?>" 
                     style="<?php echo ($statut_global === 'recherche') ? 'box-shadow: 0 0 15px rgba(13, 202, 240, 0.2);' : ''; ?>">
                    <i class="bi bi-search mb-2 fs-3 text-info"></i>
                    <div class="small fw-bold">En recherche</div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center"><i class="bi bi-chevron-right text-secondary"></i></div>
            <div class="col-md-2">
                <div class="card-custom p-3 <?php echo ($statut_global === 'entretien') ? 'border-primary' : 'opacity-50'; ?>" 
                     style="<?php echo ($statut_global === 'entretien') ? 'box-shadow: 0 0 15px rgba(13, 110, 253, 0.2);' : ''; ?>">
                    <i class="bi bi-chat-dots mb-2 fs-3 text-primary"></i>
                    <div class="small fw-bold">En entretien</div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center"><i class="bi bi-chevron-right text-secondary"></i></div>
            <div class="col-md-2">
                <div class="card-custom p-3 <?php echo ($statut_global === 'convention') ? 'border-success' : 'opacity-50'; ?>" 
                     style="<?php echo ($statut_global === 'convention') ? 'box-shadow: 0 0 15px rgba(25, 135, 84, 0.2);' : ''; ?>">
                    <i class="bi bi-file-earmark-check mb-2 fs-3 text-success"></i>
                    <div class="small fw-bold">Convention signée</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <h5 class="mb-3">Nouvelles offres — <?php echo htmlspecialchars($promo_brute); ?></h5>
                
                <?php if (count($dernieres_offres) === 0): ?>
                    <div class="card-custom p-3 text-center text-white-50 small">
                        Aucune offre récente disponible pour votre promotion.
                    </div>
                <?php else: ?>
                    <?php foreach ($dernieres_offres as $off): ?>
                        <div class="card-custom p-3 mb-3" 
                             style="cursor: pointer; border: 2px solid #8a2be2; box-shadow: 0 0 12px rgba(138, 43, 226, 0.45);" 
                             onclick="window.location.href='offres-etudiant.php';">
                            <div class="text-purple small fw-bold mb-1">
                                <i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($off['lieu'] ?? 'Lieu non spécifié'); ?>
                            </div>
                            <div class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($off['intitule']); ?>">
                                <?php echo htmlspecialchars($off['intitule']); ?>
                            </div>
                            <p class="small text-white-50 mb-2 text-truncate-2">
                                <?php echo htmlspecialchars($off['description']); ?>
                            </p>
                            <span class="status-badge bg-intranet-dark text-white-50" style="border-color: #8a2be2;">Nouveau</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-6">
                <div class="card-custom overflow-hidden mb-4 position-relative" style="height: 350px;">
                    <img src="../images/IUT-MEAUX.jpg" class="w-100 h-100 object-fit-cover opacity-50" alt="Campus">
                    <div class="position-absolute bottom-0 start-0 p-4 w-100 bg-gradient-dark">
                        <h2 class="fw-bold">Bienvenue, <?php echo htmlspecialchars($prenom_etudiant); ?> !</h2>
                        <p>Suivez l'avancement de vos démarches et consultez les offres exclusives.</p>
                        <button onclick="window.location.href='demarches-etudiant.php';" class="btn btn-purple mt-2">Voir mes démarches <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="card-custom p-3">
                            <h6 class="fw-bold"><i class="bi bi-play-circle me-2"></i> Tuto : Comment remplir sa convention ?</h6>
                            <div class="ratio ratio-16x9 mt-3">
                                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Tuto" style="border-radius: 8px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <h5 class="mb-3">Mes démarches</h5>
                <div class="card-custom p-4 text-center mb-4">
                    <div class="text-start">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span>Offres postulées</span>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo (int)$total_postules; ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span>Réponses en attente</span>
                            <span class="badge bg-warning text-dark rounded-pill">
                                <?php echo (int)$total_en_attente; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($a_une_soutenance): ?>
                    <h5 class="mb-3"><i class="bi bi-calendar3 me-2 text-info"></i>Ma Soutenance</h5>
                    <div class="card-custom p-3 border-info mb-4" style="box-shadow: 0 0 15px rgba(13, 202, 240, 0.1);">
                        <div class="small mb-1"><span class="text-muted-custom">Date :</span> <strong><?php echo date('d/m/Y', strtotime($soutenance['date'])); ?></strong></div>
                        <div class="small mb-1"><span class="text-muted-custom">Heure :</span> <strong><?php echo substr($soutenance['heure'], 0, 5); ?></strong></div>
                        <div class="small mb-2"><span class="text-muted-custom">Salle :</span> <span class="badge bg-secondary"><?php echo htmlspecialchars($soutenance['salle']); ?></span></div>
                        
                        <div class="border-top border-secondary pt-2 mt-2">
                            <div class="text-muted-custom small mb-1">Membres du Jury :</div>
                            <div class="small text-white-50"><i class="bi bi-person-badge me-1"></i> M./Mme <?php echo htmlspecialchars($soutenance['j1_prenom'] . ' ' . $soutenance['j1_nom']); ?></div>
                            <?php if (!empty($soutenance['j2_nom'])): ?>
                                <div class="small text-white-50"><i class="bi bi-person-badge me-1"></i> M./Mme <?php echo htmlspecialchars($soutenance['j2_prenom'] . ' ' . $soutenance['j2_nom']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($notes_disponibles): ?>
                    <h5 class="mb-3"><i class="bi bi-journal-check me-2 text-success"></i>Mes Notes</h5>
                    <div class="card-custom p-3 border-success mb-4" style="box-shadow: 0 0 15px rgba(25, 135, 84, 0.15);">
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-muted-custom">Note de Rapport :</span>
                            <span class="fw-bold text-white"><?php echo number_format($soutenance['note_rapport'], 2, ',', ' '); ?> / 20</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-muted-custom">Note d'Oral :</span>
                            <span class="fw-bold text-white"><?php echo number_format($soutenance['note_oral'], 2, ',', ' '); ?> / 20</span>
                        </div>
                        <div class="border-top border-secondary pt-2 mt-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-success small">Moyenne :</span>
                            <span class="badge bg-success">
                                <?php 
                                    $moyenne = ($soutenance['note_rapport'] + $soutenance['note_oral']) / 2;
                                    echo number_format($moyenne, 2, ',', ' ');
                                ?> / 20
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</html>