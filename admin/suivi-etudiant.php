<?php
session_start();

// 1. Sécurité : Vérifier si l'utilisateur est bien connecté en tant qu'admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

$id_admin = $_SESSION['user']['id_admin'];

// Récupération des informations de l'admin
$stmtInfoAdmin = $pdo->prepare("SELECT nom, prenom FROM admin WHERE id_admin = ?");
$stmtInfoAdmin->execute([$id_admin]);
$admin_connecte = $stmtInfoAdmin->fetch(PDO::FETCH_ASSOC);

$nom_admin = $admin_connecte['nom'] ?? 'Admin';
$prenom_admin = $admin_connecte['prenom'] ?? 'Espace';

// Initialisation des variables de recherche et filtres
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'prenom';
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : 'En attente';
$search_promo = isset($_GET['search_promo']) ? $_GET['search_promo'] : 'toutes';

// 2. Récupération initiale de tous les étudiants validés
$sql = "SELECT id_etudiant, matricule, nom, prenom, email, promo, gp_td, gp_tp FROM etudiant WHERE valide = 1 ORDER BY nom ASC, prenom ASC";
$stmtAll = $pdo->query($sql);
$tous_les_etudiants = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// Tableaux de stockage par promotion
$mmi1 = [];
$mmi2 = [];
$mmi3 = [];

// 3. Filtrage PHP et répartition par promotion
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
        if ($e['promo'] === 'MMI 1') {
            $mmi1[] = $e;
        } elseif ($e['promo'] === 'MMI 2') {
            $mmi2[] = $e;
        } elseif ($e['promo'] === 'MMI 3') {
            $mmi3[] = $e;
        }
    }
}

// Fonction utilitaire REPARÉE pour la compatibilité light / dark mode
function afficher_liste_accordéon($liste_etudiants, $pdo) {
    foreach ($liste_etudiants as $e) {
        $stmtDemarches = $pdo->prepare("SELECT date_contact, entreprise_cible, type_action, reponse FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC");
        $stmtDemarches->execute([$e['id_etudiant']]);
        $demarches = $stmtDemarches->fetchAll(PDO::FETCH_ASSOC);
        
        $collapseId = "collapse_" . $e['id_etudiant'];
        $headingId = "heading_" . $e['id_etudiant'];
        $promoIdClean = str_replace(' ', '', $e['promo']);

        if ($e['statut_actuel'] === 'Validé' || $e['statut_actuel'] === 'Convention signée') {
            $card_badge_class = "bg-success text-white";
        } elseif ($e['statut_actuel'] === 'Refusé') {
            $card_badge_class = "bg-danger text-white";
        } elseif ($e['statut_actuel'] === 'En attente') {
            $card_badge_class = "bg-warning text-dark";
        } else {
            $card_badge_class = "bg-primary text-white";
        }
        ?>
        <div class="card-custom mb-3 overflow-hidden shadow-sm border">
            <div class="p-3 border-bottom card-header-custom" 
                 id="<?= $headingId ?>" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#<?= $collapseId ?>" 
                 aria-expanded="false"
                 aria-controls="<?= $collapseId ?>"
                 style="cursor: pointer;">
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold fs-4 text-header-custom text-truncate" style="max-width: 70%;"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></span>
                    <span class="badge bg-purple font-monospace px-3 py-2 fs-6 rounded shadow-sm"><?= count($demarches) ?> action(s)</span>
                </div>

                <div class="mb-3">
                    <div class="text-muted-custom small text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Statut actuel</div>
                    <span class="badge <?= $card_badge_class ?> font-monospace px-3 py-2 fs-5 w-100 text-center shadow-sm fw-bold">
                        <?= htmlspecialchars($e['statut_actuel']) ?>
                    </span>
                </div>

                <div class="row g-2 text-muted-custom border-top pt-2 border-secondary-subtle" style="font-size: 0.95rem;">
                    <div class="col-12 mb-1 text-truncate fw-semibold text-header-custom">
                        <i class="bi bi-envelope text-purple me-2"></i><?= htmlspecialchars($e['email']) ?>
                    </div>
                    <div class="col-6">
                        <i class="bi bi-collection text-secondary me-1"></i> TD : <strong class="text-header-custom fs-6"><?= htmlspecialchars($e['gp_td']) ?></strong>
                    </div>
                    <div class="col-6">
                        <i class="bi bi-people text-secondary me-1"></i> TP : <strong class="text-header-custom fs-6"><?= htmlspecialchars($e['gp_tp']) ?></strong>
                    </div>
                    <div class="col-12 mt-2 pt-1 border-top border-secondary-subtle font-monospace text-end text-muted-custom" style="font-size: 0.8rem;">
                        Matricule : <span class="text-info fw-bold fs-6"><?= htmlspecialchars($e['matricule']) ?></span>
                    </div>
                </div>
            </div>
            
            <div id="<?= $collapseId ?>" class="collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#accordionSuivi_<?= $promoIdClean ?>">
                <div class="p-3 accordion-body-custom border-top">
                    <h5 class="text-purple fw-bold mb-3 border-bottom pb-1 border-secondary-subtle" style="font-size: 1.1rem;">
                        <i class="bi bi-journal-text me-2"></i>Historique détaillé des démarches
                    </h5>

                    <?php if (empty($demarches)): ?>
                        <div class="text-center py-3 text-muted-custom fs-6">
                            <i class="bi bi-info-circle me-1"></i> Aucune démarche déclarée par cet étudiant.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($demarches as $d): 
                                $b_class = ($d['reponse'] === 'Validé' || $d['reponse'] === 'Convention signée') ? "bg-success text-white" : (($d['reponse'] === 'Refusé') ? "bg-danger text-white" : (($d['reponse'] === 'En attente') ? "bg-warning text-dark" : "bg-primary text-white"));
                            ?>
                                <div class="p-3 rounded border shadow-sm sub-card-custom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-info font-monospace fw-bold fs-6">
                                            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y', strtotime($d['date_contact'])) ?>
                                        </span>
                                        <span class="badge <?= $b_class ?> font-monospace fw-bold px-2.5 py-1.5 fs-6 shadow-sm">
                                            <?= htmlspecialchars($d['reponse']) ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted-custom d-block text-uppercase" style="font-size: 0.7rem;">Entreprise ciblée</small>
                                        <strong class="text-header-custom fs-5"><i class="bi bi-building me-2 text-secondary"></i><?= htmlspecialchars($d['entreprise_cible']) ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted-custom d-block text-uppercase" style="font-size: 0.7rem;">Action / Étape menée</small>
                                        <div class="text-header-custom fs-6 ps-1 border-start border-purple mt-1 py-1 type-action-badge rounded px-2">
                                            <?= htmlspecialchars($d['type_action']) ?>
                                        </div>
                                    </div>
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

$column_class = ($search_promo === 'toutes') ? 'col-xl-4 col-md-6 col-12' : 'col-12';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des Étudiants - Administration</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style.css">
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
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="dashboard-admin.php">
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="suivi-etudiant.php">
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
                    <div class="pe-4">
                        <button id="themeChangerBtn" class="theme-switch-btn" title="Changer le mode de couleur">
                            <i id="iconeTheme" class="bi bi-moon-stars-fill text-white"></i>
                        </button>
                    </div>
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
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm" title="Déconnexion"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-4 py-5 flex-grow-1">
        <div class="mb-4">
            <h2 class="fw-bold text-purple"><i class="bi bi-people-fill me-2"></i>Suivi des démarches par promotion</h2>
        </div>

        <div class="card-custom p-3 mb-4 bg-intranet-dark-wrapper">
            <form method="GET" action="suivi-etudiant.php" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select bg-dark text-white border-secondary fw-bold" id="search_type" name="search_type" onchange="toggleSearchInputs()">
                        <option value="prenom" <?php echo $search_type === 'prenom' ? 'selected' : ''; ?>>Rechercher par Prénom</option>
                        <option value="matricule" <?php echo $search_type === 'matricule' ? 'selected' : ''; ?>>Rechercher par Matricule</option>
                        <option value="statut" <?php echo $search_type === 'statut' ? 'selected' : ''; ?>>Filtrer par Statut</option>
                    </select>
                </div>
                
                <div class="col-md-4" id="wrapper_text_input">
                    <input type="text" class="form-control bg-dark text-white border-secondary" id="search_query" name="search_query" placeholder="Tapez votre recherche..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>

                <div class="col-md-4" id="wrapper_status_select" style="display: none;">
                    <select class="form-select bg-dark text-white border-secondary" id="search_status" name="search_status">
                        <option value="En attente" <?php echo $search_status === 'En attente' ? 'selected' : ''; ?>>⏳ En attente de réponse</option>
                        <option value="Entretien prévu" <?php echo $search_status === 'Entretien prévu' ? 'selected' : ''; ?>>📅 Entretien prévu</option>
                        <option value="Entretien" <?php echo $search_status === 'Entretien' ? 'selected' : ''; ?>>📅 Entretien passé</option>
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
                    <div class="p-3 card-custom h-100 column-promo-wrapper" style="border-top: 4px solid #0dcaf0;">
                        <h4 class="fw-bold text-info mb-3 border-bottom pb-2 border-secondary-subtle"><i class="bi bi-mortarboard me-2"></i>MMI 1</h4>
                        <?php if (empty($mmi1)): ?>
                            <p class="text-muted-custom small ps-1">Aucun étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI1"><?php afficher_liste_accordéon($mmi1, $pdo); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($search_promo === 'toutes' || $search_promo === 'MMI 2'): ?>
                <div class="<?= $column_class ?>">
                    <div class="p-3 card-custom h-100 column-promo-wrapper" style="border-top: 4px solid #0dcaf0;">
                        <h4 class="fw-bold text-info mb-3 border-bottom pb-2 border-secondary-subtle"><i class="bi bi-mortarboard me-2"></i>MMI 2</h4>
                        <?php if (empty($mmi2)): ?>
                            <p class="text-muted-custom small ps-1">Aucun étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI2"><?php afficher_liste_accordéon($mmi2, $pdo); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($search_promo === 'toutes' || $search_promo === 'MMI 3'): ?>
                <div class="<?= $column_class ?>">
                    <div class="p-3 card-custom h-100 column-promo-wrapper" style="border-top: 4px solid #0dcaf0;">
                        <h4 class="fw-bold text-info mb-3 border-bottom pb-2 border-secondary-subtle"><i class="bi bi-mortarboard me-2"></i>MMI 3</h4>
                        <?php if (empty($mmi3)): ?>
                            <p class="text-muted-custom small ps-1">Aucun étudiant trouvé.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="accordionSuivi_MMI3"><?php afficher_liste_accordéon($mmi3, $pdo); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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