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

// Récupération des informations de la session
$id_etudiant = $_SESSION['user']['id_etudiant'];
$nom_etudiant = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : 'Nom';
$prenom_etudiant = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : 'Prénom';

// 1. Récupérer la promotion de l'étudiant pour filtrer les offres
// (On nettoie la chaîne pour correspondre aux ENUM 'MMI1', 'MMI2', 'MMI3' de la table offre)
$stmtPromo = $pdo->prepare("SELECT promo FROM etudiant WHERE id_etudiant = ?");
$stmtPromo->execute([$id_etudiant]);
$promo_brute = $stmtPromo->fetchColumn();
$promo_filtre = str_replace(' ', '', $promo_brute); // Transforme "MMI 2" ou "MMI 2" en "MMI2"

// 2. Récupérer les offres correspondantes à sa promotion
$stmtOffres = $pdo->prepare("SELECT * FROM offre WHERE promotion_visee = ? ORDER BY id_offre DESC");
$stmtOffres->execute([$promo_filtre]);
$offres = $stmtOffres->fetchAll(PDO::FETCH_ASSOC);

// 3. Traitement optionnel de postulation (si l'étudiant clique sur "Postuler")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_postuler'])) {
    $id_offre_cible = $_POST['id_offre'];
    $entreprise_nom = $_POST['entreprise_nom'];
    $date_aujourdhui = date('Y-m-d');

    // On ajoute automatiquement la démarche dans l'historique de l'étudiant
    $stmtPostuler = $pdo->prepare("
        INSERT INTO historique (entreprise_cible, date_contact, type_action, reponse, id_etudiant) 
        VALUES (?, ?, 'Candidature Intranet', 'En attente', ?)
    ");
    $stmtPostuler->execute([$entreprise_nom, $date_aujourdhui, $id_etudiant]);
    
    $msg_success = "Votre candidature pour l'offre chez " . htmlspecialchars($entreprise_nom) . " a été transmise et ajoutée à vos démarches !";
    
    // Rafraîchir les offres pour éviter le renvoi de formulaire
    header("Location: offres-etudiant.php?success=" . urlencode($msg_success));
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Offres - Etudiant</title> 
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
    
    <!-- APPLICATION IMMÉDIATE DU MODE SOMBRE SI BESOIN -->
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="offres-etudiant.php">
                            <i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Offres
                        </a>
                    </li>
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

    <main class="flex-grow-1 container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0"><i class="bi bi-grid-3x3-gap me-2 text-purple"></i> Offres de stages disponibles — <?php echo htmlspecialchars($promo_brute); ?></h3>
            <span class="badge bg-secondary px-3 py-2">Filtre automatique activé</span>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (count($offres) === 0): ?>
                <div class="col-12">
                    <div class="card-custom p-5 text-center text-muted">
                        <i class="bi bi-emoji-frown fs-1 mb-3 text-secondary"></i>
                        <p class="m-0 fs-5">Aucune offre de stage n'est actuellement publiée pour la promotion <?php echo htmlspecialchars($promo_brute); ?>.</p>
                        <small class="text-muted-custom">Revenez plus tard ou continuez vos démarches personnelles.</small>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($offres as $offre): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between border-secondary">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold text-white m-0"><?php echo htmlspecialchars($offre['intitule']); ?></h5>
                                    <span class="badge bg-purple ms-2"><?php echo htmlspecialchars($offre['promotion_visee']); ?></span>
                                </div>
                                
                                <div class="small text-muted-custom mb-3">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo htmlspecialchars($offre['lieu'] ?? 'Non spécifié'); ?> 
                                    <?php if (!empty($offre['duree'])): ?>
                                        <span class="mx-2">|</span> <i class="bi bi-stopwatch me-1 text-info"></i> <?php echo htmlspecialchars($offre['duree']); ?> jours
                                    <?php endif; ?>
                                </div>
                                
                                <p class="small text-white-50 mb-3" style="text-align: justify;">
                                    <?php echo nl2br(htmlspecialchars($offre['description'])); ?>
                                </p>

                                <?php if (!empty($offre['competences'])): ?>
                                    <div class="mb-3">
                                        <div class="small text-muted-custom mb-1 fw-bold">Compétences attendues :</div>
                                        <p class="small text-info m-0"><em><?php echo htmlspecialchars($offre['competences']); ?></em></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="border-top border-secondary pt-3 mt-3 d-flex justify-content-between align-items-center">
                                <div class="small text-success fw-bold">
                                    <i class="bi bi-cash-coin me-1"></i> 
                                    <?php echo ($offre['remuneration'] > 0) ? number_format($offre['remuneration'], 2, ',', ' ') . ' €' : 'Gratification minimale'; ?>
                                </div>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="id_offre" value="<?php echo $offre['id_offre']; ?>">
                                    <input type="hidden" name="entreprise_nom" value="Entreprise Partenaire (Offre N°<?php echo $offre['id_offre']; ?>)">
                                    <button type="submit" name="action_postuler" class="btn btn-purple btn-sm px-3" onclick="return confirm('Confirmez-vous votre candidature à cette offre ?');">
                                        Postuler <i class="bi bi-arrow-right-short ms-1"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        
        <!-- SCRIPT DE GESTION DU CLIC THEME -->
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