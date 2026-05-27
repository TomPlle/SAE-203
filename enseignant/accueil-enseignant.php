<?php
session_start();

// 1. Sécurité : Vérifier si l'enseignant est bien connecté
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

// Récupération des informations de l'enseignant connecté depuis la session
$id_enseignant = $_SESSION['user']['id_enseignant']; 
$nom_enseignant = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : 'Nom';
$prenom_enseignant = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : 'Prénom';
$role_enseignant = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'Enseignant';

// 2. STATISTIQUES ENSEIGNANT
// Compte le nombre de stages encadrés en tant que tuteur
$stmtStages = $pdo->prepare("SELECT COUNT(*) FROM stage WHERE id_enseignant = ?");
$stmtStages->execute([$id_enseignant]);
$total_stages_tuteur = $stmtStages->fetchColumn();

// Compte le nombre de soutenances à évaluer (Jury 1 ou Jury 2)
$stmtSoutenances = $pdo->prepare("SELECT COUNT(*) FROM soutenance WHERE id_enseignant_1 = ? OR id_enseignant_2 = ?");
$stmtSoutenances->execute([$id_enseignant, $id_enseignant]);
$total_soutenances_jury = $stmtSoutenances->fetchColumn();

// 3. RECUPÉRATION DES ACTIONS URVENTES (Ex: Visites de stage non planifiées ou notes manquantes)
$stmtVisitesManquantes = $pdo->prepare("SELECT COUNT(*) FROM stage WHERE id_enseignant = ? AND date_visite IS NULL");
$stmtVisitesManquantes->execute([$id_enseignant]);
$visites_a_planifier = $stmtVisitesManquantes->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Enseignant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">
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
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="accueil-enseignant.php">
                            <i class="bi bi-house-door me-2 fs-4"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-stages.php">
                            <i class="bi bi-person-video3 me-2 fs-4"></i> Suivi des Stages
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="soutenances-enseignant.php">
                            <i class="bi bi-calendar-event me-2 fs-4"></i> Soutenances & Notes
                        </a>
                    </li>
                    <!-- AJOUT NAVBAR : Onglet Catalogue Offres intégré -->
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="offres-enseignant.php">
                            <i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Catalogue Offres
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
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
        <div class="row g-3 mb-4 justify-content-center text-center">
            
            <div class="col-md-2">
                <div class="card-custom p-3 border-info" style="box-shadow: 0 0 15px rgba(13, 202, 240, 0.15);">
                    <i class="bi bi-briefcase mb-2 fs-3 text-info"></i>
                    <div class="small fw-bold"><?php echo (int)$total_stages_tuteur; ?> Stages suivis</div>
                </div>
            </div>
            
            <div class="col-auto d-flex align-items-center"><i class="bi bi-arrow-right text-secondary"></i></div>
            
            <div class="col-md-2">
                <div class="card-custom p-3 <?php echo ($visites_a_planifier > 0) ? 'border-warning' : 'opacity-50'; ?>">
                    <i class="bi bi-calendar-check mb-2 fs-3 text-warning"></i>
                    <div class="small fw-bold"><?php echo (int)$visites_a_planifier; ?> Visites à planifier</div>
                </div>
            </div>
            
            <div class="col-auto d-flex align-items-center"><i class="bi bi-arrow-right text-secondary"></i></div>
            
            <div class="col-md-2">
                <div class="card-custom p-3 border-success" style="box-shadow: 0 0 15px rgba(25, 135, 84, 0.15);">
                    <i class="bi bi-journal-check mb-2 fs-3 text-success"></i>
                    <div class="small fw-bold"><?php echo (int)$total_soutenances_jury; ?> Évaluations Jury</div>
                </div>
            </div>
            
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <h5 class="mb-3">Rappels Pédagogiques</h5>
                
                <div class="card-custom p-3 mb-3 border-danger">
                    <div class="text-danger small fw-bold mb-1">Règle des 7 jours</div>
                    <div class="fw-bold mb-2">Saisie des Évaluations</div>
                    <p class="small text-white-50 mb-2">Conformément aux consignes du BUT MMI, les notes de rapport et d'oral doivent être publiées au maximum 1 semaine après la soutenance.</p>
                    <span class="status-badge border-danger text-danger">Strict</span>
                </div>

                <div class="card-custom p-3">
                    <div class="text-primary small fw-bold mb-1">Visite de stage</div>
                    <div class="fw-bold mb-2">Suivi sur le terrain</div>
                    <p class="small text-white-50 mb-2">N'oubliez pas de renseigner la date de votre visite de stage pour chaque étudiant encadré dès confirmation avec le maître de stage.</p>
                    <span class="status-badge">Information</span>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom overflow-hidden mb-4 position-relative" style="height: 220px;">
                    <img src="../images/IUT-MEAUX.jpg" class="w-100 h-100 object-fit-cover opacity-50" alt="Campus">
                    <div class="position-absolute bottom-0 start-0 p-4 w-100 bg-gradient-dark">
                        <h2 class="fw-bold">Espace Enseignant, <?php echo htmlspecialchars($prenom_enseignant); ?></h2>
                        <p>Supervisez l'avancement des conventions de vos tuteurs et complétez les grilles d'évaluation des soutenances MMI.</p>
                        <button onclick="window.location.href='suivi-stages.php';" class="btn btn-purple mt-2">Accéder à mes étudiants <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <div class="card-custom p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i> Aperçu rapide de votre planning de jury</h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover m-0 align-middle small">
                            <thead>
                                <tr class="text-muted-custom border-secondary">
                                    <th>Date & Heure</th>
                                    <th>Étudiant</th>
                                    <th>Salle</th>
                                    <th>Rôle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Requête pour récupérer les 3 prochaines soutenances planifiées pour cet enseignant
                                $stmtListSout = $pdo->prepare("
                                    SELECT s.date, s.heure, s.salle, et.nom, et.prenom,
                                           IF(s.id_enseignant_1 = ?, 'Jury Principal', 'Co-jury') as position
                                    FROM soutenance s
                                    JOIN etudiant et ON s.id_etudiant = et.id_etudiant
                                    WHERE s.id_enseignant_1 = ? OR s.id_enseignant_2 = ?
                                    ORDER BY s.date ASC, s.heure ASC LIMIT 3
                                ");
                                $stmtListSout->execute([$id_enseignant, $id_enseignant, $id_enseignant]);
                                $soutenances = $stmtListSout->fetchAll(PDO::FETCH_ASSOC);

                                if (count($soutenances) > 0) {
                                    foreach ($soutenances as $sout) {
                                        echo "<tr class='border-secondary'>";
                                        echo "<td>".date('d/m/Y', strtotime($sout['date']))." à ".substr($sout['heure'], 0, 5)."</td>";
                                        echo "<td class='fw-bold'>".htmlspecialchars($sout['prenom']." ".$sout['nom'])."</td>";
                                        echo "<td><span class='badge bg-secondary'>".htmlspecialchars($sout['salle'])."</span></td>";
                                        echo "<td><span class='text-info'>".$sout['position']."</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted py-3'>Aucune soutenance planifiée pour le moment.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <h5 class="mb-3">Mes Actions</h5>
                <div class="card-custom p-4 text-center">
                    <div class="text-start">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span>Total Étudiants suivis</span>
                            <span class="badge bg-primary rounded-pill fs-6 px-3">
                                <?php echo (int)$total_stages_tuteur; ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span>Soutenances à évaluer</span>
                            <span class="badge bg-success rounded-pill fs-6 px-3">
                                <?php echo (int)$total_soutenances_jury; ?>
                            </span>
                        </div>
                        <hr class="border-secondary my-3">
                        <div class="d-grid">
                            <a href="soutenances-enseignant.php" class="btn btn-outline-light btn-sm"><i class="bi bi-pencil-square me-2"></i> Saisir des notes</a>
                        </div>
                    </div>
                </div>
            </div>
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
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</html>