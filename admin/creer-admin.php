<?php
session_start();

// 1. Sécurité : Vérifier si l'utilisateur est bien connecté en tant qu'admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit();
}

require_once '../php/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$id_admin = $_SESSION['user']['id_admin'];

// --- AJOUTE CE BLOC POUR RÉPARER L'ERREUR EN ALLANT CHERCHER DANS LA BDD ---
$stmtInfoAdmin = $pdo->prepare("SELECT nom, prenom FROM admin WHERE id_admin = ?");
$stmtInfoAdmin->execute([$id_admin]);
$admin_connecte = $stmtInfoAdmin->fetch(PDO::FETCH_ASSOC);

$nom_admin = $admin_connecte['nom'] ?? 'Admin';
$prenom_admin = $admin_connecte['prenom'] ?? 'Espace';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un Administrateur - G-Stage</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="dashboard-admin.php" style="height: 100%;">
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
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-etudiant.php">
                            <i class="bi bi-person-lines-fill me-2 fs-4"></i> Suivi Étudiants
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="creer-admin.php">
                            <i class="bi bi-person-lines-fill me-2 fs-4"></i> Créer un administrateur
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
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
        <div class="card-custom p-4 w-100" style="max-width: 500px;">
            <h2 class="text-center text-purple mb-4"><i class="bi bi-shield-lock-fill"></i> Nouvel Administrateur</h2>
            
            <form id="adminForm" action="inscription-admin-action.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label small fw-bold text-muted-custom">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control" required placeholder="Dupont">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="prenom" class="form-label small fw-bold text-muted-custom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" required placeholder="Jean">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="tel" class="form-label small fw-bold text-muted-custom">Téléphone</label>
                    <input type="tel" id="tel" name="tel" class="form-control" placeholder="0601020304">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold text-muted-custom">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="admin@univ-eiffel.fr">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-bold text-muted-custom">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-bold text-muted-custom">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="••••••••">
                    <div id="error-msg" class="text-danger small mt-2" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill"></i> Les mots de passe ne correspondent pas.
                    </div>
                </div>

                <button type="submit" class="btn btn-purple w-100 fw-bold text-uppercase py-2 mb-3">Créer le compte Admin</button>
            </form>
        </div>
    </main>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4">
            <p class="m-0 text-muted-custom small">&copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel</p>
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