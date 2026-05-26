<?php
session_start();

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

// Traitement de la mise à jour de la visite ou de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update'])) {
    $id_stage = $_POST['id_stage'];
    $date_visite = !empty($_POST['date_visite']) ? $_POST['date_visite'] : null;
    $etat_validation = $_POST['etat_validation'];

    $stmtUpdate = $pdo->prepare("UPDATE stage SET date_visite = ?, etat_validation = ? WHERE id_stage = ? AND id_enseignant = ?");
    $stmtUpdate->execute([$date_visite, $etat_validation, $id_stage, $id_enseignant]);
    $msg_success = "Suivi du stage mis à jour avec succès !";
}

// Récupération des stages de l'enseignant
$stmtMyStages = $pdo->prepare("
    SELECT s.*, et.nom AS et_nom, et.prenom AS et_prenom, et.promo, ent.nom_societe, r.nom AS r_nom, r.prenom AS r_prenom
    FROM stage s
    JOIN etudiant et ON s.id_etudiant = et.id_etudiant
    JOIN entreprise ent ON s.id_entreprise = ent.id_entreprise
    JOIN responsable_de_stage r ON s.id_responsable = r.id_responsable
    WHERE s.id_enseignant = ?
");
$stmtMyStages->execute([$id_enseignant]);
$stages = $stmtMyStages->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des Stages - Enseignant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">
    <header class="navbar navbar-expand-lg bg-intranet-dark text-white p-0 py-2 border-bottom border-secondary">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-white d-flex align-items-center m-0 p-0 pe-4 border-end border-secondary" href="accueil-enseignant.php">
                <img src="../images/logo-noir-blanc.png" alt="Logo" class="me-3" style="height: 50px; width: auto;"> 
                <div class="lh-sm">
                    <div class="fw-bold text-uppercase" style="font-size: 1.1rem; letter-spacing: 1px;">GESTIONNAIRE DE STAGE</div>
                    <div class="text-muted-custom" style="font-size: 0.8rem;">UNIVERSITÉ GUSTAVE EIFFEL</div>
                </div>
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto align-items-stretch border-start border-end border-secondary">
                    <li class="nav-item"><a class="nav-link nav-link-custom d-flex align-items-center" href="accueil-enseignant.php"><i class="bi bi-house-door me-2 fs-4"></i> Accueil</a></li>
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom active d-flex align-items-center" href="suivi-stages.php"><i class="bi bi-person-video3 me-2 fs-4"></i> Suivi des Stages</a></li>
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom d-flex align-items-center" href="soutenances-enseignant.php"><i class="bi bi-calendar-event me-2 fs-4"></i> Soutenances & Notes</a></li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <div class="ps-4 text-end me-3">
                        <div class="text-muted-custom" style="font-size: 0.7rem;">Espace Enseignant</div>
                        <div class="fw-bold text-white text-uppercase" style="font-size: 0.95rem;"><?php echo htmlspecialchars($prenom_enseignant . ' ' . $nom_enseignant); ?></div>
                    </div>
                    <div class="pe-3"><a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></div>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-4 py-4">
        <h3 class="mb-4 fw-bold"><i class="bi bi-briefcase me-2 text-purple"></i> Portefeuille des Stages Tutorés</h3>

        <?php if (isset($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4"><?php echo $msg_success; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (count($stages) === 0): ?>
                <div class="col-12">
                    <div class="card-custom p-5 text-center text-muted">Aucun étudiant ne vous a été attribué comme tuteur pour le moment.</div>
                </div>
            <?php else: ?>
                <?php foreach ($stages as $stage): ?>
                    <div class="col-xl-6">
                        <div class="card-custom p-4 h-100 border-secondary">
                            <div class="d-flex justify-content-between align-items-start mb-3 border-bottom border-secondary pb-2">
                                <div>
                                    <h5 class="fw-bold m-0 text-purple"><?php echo htmlspecialchars($stage['et_prenom'] . ' ' . $stage['et_nom']); ?></h5>
                                    <span class="small text-muted-custom">Promotion : <?php echo htmlspecialchars($stage['promo']); ?></span>
                                </div>
                                <span class="badge bg-secondary px-3 py-2">Conv. N° <?php echo htmlspecialchars($stage['num_convention']); ?></span>
                            </div>

                            <div class="row g-3 mb-3 small">
                                <div class="col-md-6">
                                    <div class="text-muted-custom">Entreprise & Sujet :</div>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($stage['nom_societe']); ?></div>
                                    <div class="text-muted"><?php echo htmlspecialchars($stage['sujet']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted-custom">Maître de Stage :</div>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($stage['r_prenom'] . ' ' . $stage['r_nom']); ?></div>
                                    <div class="text-muted-custom">Période : Du <?php echo date('d/m/Y', strtotime($stage['date_deb'])); ?> au <?php echo date('d/m/Y', strtotime($stage['date_fin'])); ?></div>
                                </div>
                            </div>

                            <form method="POST" action="" class="bg-intranet-dark p-3 rounded border border-secondary mt-2">
                                <input type="hidden" name="id_stage" value="<?php echo $stage['id_stage']; ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted-custom m-0 mb-1">Date de la visite pédagogique</label>
                                        <input type="date" name="date_visite" class="form-control form-control-sm bg-dark text-white border-secondary" value="<?php echo $stage['date_visite']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted-custom m-0 mb-1">État validation</label>
                                        <select name="etat_validation" class="form-select form-select-sm bg-dark text-white border-secondary">
                                            <option value="En cours" <?php echo ($stage['etat_validation'] === 'En cours') ? 'selected' : ''; ?>>En cours</option>
                                            <option value="Validé" <?php echo ($stage['etat_validation'] === 'Validé') ? 'selected' : ''; ?>>Validé (Terminé)</option>
                                            <option value="Problème" <?php echo ($stage['etat_validation'] === 'Problème') ? 'selected' : ''; ?>>Alerte / Problème</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button type="submit" name="action_update" class="btn btn-purple btn-sm"><i class="bi bi-check-lg"></i> Sauver</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4">
            <p class="m-0 text-muted-custom" style="font-size: 0.85rem;">&copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel</p>
        </div>
    </footer>
</body>
</html>