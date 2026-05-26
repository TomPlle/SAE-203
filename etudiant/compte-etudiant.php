<?php
session_start();

// Sécurité : Si l'étudiant n'est pas connecté ou n'a pas le bon rôle, retour à l'index
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: ../index.html");
    exit();
}

// Récupération des informations de la session
$nom_etudiant = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : 'Nom';
$prenom_etudiant = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : 'Prénom';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte - Etudiant</title> 
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
            <!-- Section Principale Nav-Bar -->
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <!-- Accueil -->
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="accueil-etudiant.php">
                            <i class="bi bi-house-door me-2 fs-4"></i> Accueil
                        </a>
                    </li>
                    <!-- Démarches -->
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="demarches-etudiant.php">
                            <i class="bi bi-folder me-2 fs-4"></i> Démarches
                        </a>
                    </li>
                    <!-- Offres -->
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="offres-etudiant.php">
                            <i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Offres
                        </a>
                    </li>
                </ul>
                <!-- Section profil Nav-Bar -->
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
                    <div class="ms-auto">
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main class="flex-grow-1">

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