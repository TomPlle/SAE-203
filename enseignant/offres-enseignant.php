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

// Détermination des rôles de responsables
$est_responsable_mmi1 = ($role_enseignant === 'Responsable-Stage-MMI1');
$est_responsable_mmi2 = ($role_enseignant === 'Responsable-Stage-MMI2');
$est_responsable_mmi3 = ($role_enseignant === 'Responsable-Stage-MMI3');
$est_un_responsable  = ($est_responsable_mmi1 || $est_responsable_mmi2 || $est_responsable_mmi3);

// Définition de la promo prioritaire de l'enseignant connecté
$promo_prioritaire = '';
if ($est_responsable_mmi1) { $promo_prioritaire = 'MMI1'; }
if ($est_responsable_mmi2) { $promo_prioritaire = 'MMI2'; }
if ($est_responsable_mmi3) { $promo_prioritaire = 'MMI3'; }

$msg_success = "";
$msg_error = "";

// -------------------------------------------------------------------------
// TRAITEMENTS DES FORMULAIRES (AJOUT, MODIFICATION, SUPPRESSION)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AJOUT D'UNE OFFRE
    if (isset($_POST['action_creer_offre'])) {
        if (!$est_un_responsable) {
            $msg_error = "Action refusée : Privilèges insuffisants.";
        } else {
            $intitule = trim($_POST['intitule']);
            $description = trim($_POST['description']);
            $competences = trim($_POST['competences']);
            $duree = trim($_POST['duree']);
            $lieu = trim($_POST['lieu']);
            $remuneration = !empty($_POST['remuneration']) ? $_POST['remuneration'] : 0;

            $stmt = $pdo->prepare("INSERT INTO offre (intitule, description, competences, duree, lieu, remuneration, promotion_visee) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$intitule, $description, $competences, $duree, $lieu, $remuneration, $promo_prioritaire]);
            $msg_success = "L'offre de stage a été publiée avec succès !";
        }
    }

    // MODIFICATION D'UNE OFFRE EXISTING
    if (isset($_POST['action_modifier_offre'])) {
        $id_offre = $_POST['id_offre'];
        $intitule = trim($_POST['intitule']);
        $description = trim($_POST['description']);
        $competences = trim($_POST['competences']);
        $duree = trim($_POST['duree']);
        $lieu = trim($_POST['lieu']);
        $remuneration = !empty($_POST['remuneration']) ? $_POST['remuneration'] : 0;

        // Sécurité Verrou : S'assurer que le responsable modifie bien sa propre promo
        $stmtCheck = $pdo->prepare("SELECT promotion_visee FROM offre WHERE id_offre = ?");
        $stmtCheck->execute([$id_offre]);
        $promo_offre = $stmtCheck->fetchColumn();

        if ($promo_offre !== $promo_prioritaire) {
            $msg_error = "Action refusée : Vous ne pouvez pas modifier une offre de la promotion " . $promo_offre;
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE offre SET intitule = ?, description = ?, competences = ?, duree = ?, lieu = ?, remuneration = ? WHERE id_offre = ?");
            $stmtUpdate->execute([$intitule, $description, $competences, $duree, $lieu, $remuneration, $id_offre]);
            $msg_success = "La fiche de stage a été mise à jour !";
        }
    }

    // SUPPRESSION D'UNE OFFRE
    if (isset($_POST['action_supprimer_offre'])) {
        $id_offre = $_POST['id_offre'];

        $stmtCheck = $pdo->prepare("SELECT promotion_visee FROM offre WHERE id_offre = ?");
        $stmtCheck->execute([$id_offre]);
        $promo_offre = $stmtCheck->fetchColumn();

        if ($promo_offre !== $promo_prioritaire) {
            $msg_error = "Action refusée : Droits de suppression insuffisants.";
        } else {
            $stmtDelete = $pdo->prepare("DELETE FROM offre WHERE id_offre = ?");
            $stmtDelete->execute([$id_offre]);
            $msg_success = "L'offre de stage a été retirée du catalogue.";
        }
    }
}

// -------------------------------------------------------------------------
// GESTION DU FILTRAGE ET DU TRI CHRONOLOGIQUE ET PAR PROMO PRIORITAIRE
// -------------------------------------------------------------------------
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_promo = isset($_GET['search_promo']) ? $_GET['search_promo'] : 'toutes';

$conditions = [];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(intitule LIKE ? OR description LIKE ? OR competences LIKE ? OR lieu LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($search_promo !== 'toutes') {
    $conditions[] = "promotion_visee = ?";
    $params[] = str_replace(' ', '', $search_promo); 
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

if (!empty($promo_prioritaire)) {
    $order_by = "ORDER BY FIELD(promotion_visee, ?) DESC, id_offre DESC";
    array_unshift($params, $promo_prioritaire); 
} else {
    $order_by = "ORDER BY promotion_visee ASC, id_offre DESC";
}

$stmtOffres = $pdo->prepare("SELECT * FROM offre $where_clause $order_by");
$stmtOffres->execute($params);
$offres = $stmtOffres->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue des Offres - Enseignant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
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
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-stages.php"><i class="bi bi-person-video3 me-2 fs-4"></i> Suivi des Stages</a></li>
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom d-flex align-items-center" href="soutenances-enseignant.php"><i class="bi bi-calendar-event me-2 fs-4"></i> Soutenances & Notes</a></li>
                    <!-- NAVBAR CORRIGÉE : Onglet "Catalogue Offres" configuré en actif ici -->
                    <li class="nav-item border-start border-secondary"><a class="nav-link nav-link-custom active d-flex align-items-center" href="offres-enseignant.php"><i class="bi bi-grid-3x3-gap me-2 fs-4"></i> Catalogue Offres</a></li>
                </ul>
                <div class="d-flex align-items-center h-100 separator-right">
                    <a class="text-decoration-none" href="compte-enseignant.php">
                    <div class="ps-4 text-end me-3">
                        <div class="text-muted-custom" style="font-size: 0.7rem;"><center>Espace <?php echo htmlspecialchars($role_enseignant); ?></center></div>
                        <div class="fw-bold text-white text-uppercase" style="font-size: 0.95rem;"><?php echo htmlspecialchars($prenom_enseignant . ' ' . $nom_enseignant); ?></div>
                    </div>
                    </a>
                    <div class="ms-2 pe-3">
                        <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm" title="Déconnexion"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-4 py-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0"><i class="bi bi-grid-3x3-gap me-2 text-purple"></i> Catalogue général des offres de stages</h2>
            <div>
                <?php if ($est_un_responsable): ?>
                    <button class="btn btn-purple btn-sm" data-bs-toggle="modal" data-bs-target="#modalNouvelleOffre">
                        <i class="bi bi-plus-circle me-1"></i> Déposer une offre (<?= $promo_prioritaire ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($msg_success)): ?>
            <div class="alert alert-success bg-success text-white border-0 mb-4"><?php echo $msg_success; ?></div>
        <?php endif; ?>
        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 mb-4"><?php echo $msg_error; ?></div>
        <?php endif; ?>

        <!-- BARRE DE RECHERCHE FILTRANTE -->
        <div class="card-custom p-3 mb-4 bg-intranet-dark border-secondary">
            <form method="GET" action="offres-enseignant.php" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="text" class="form-control bg-dark text-white border-secondary" name="search_query" placeholder="Rechercher par mots-clés (Intitulé, compétences, lieu...)" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select bg-dark text-white border-secondary fw-bold text-info" name="search_promo">
                        <option value="toutes" <?php echo $search_promo === 'toutes' ? 'selected' : ''; ?>>🎓 Toutes les promotions</option>
                        <option value="MMI 1" <?php echo $search_promo === 'MMI 1' ? 'selected' : ''; ?>>Promo : MMI 1</option>
                        <option value="MMI 2" <?php echo $search_promo === 'MMI 2' ? 'selected' : ''; ?>>Promo : MMI 2</option>
                        <option value="MMI 3" <?php echo $search_promo === 'MMI 3' ? 'selected' : ''; ?>>Promo : MMI 3</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button type="submit" class="btn btn-purple fw-bold">Filtrer</button></div>
            </form>
        </div>

        <!-- GRILLE DE CATALOGUE -->
        <div class="row g-4">
            <?php if (count($offres) === 0): ?>
                <div class="col-12">
                    <div class="card-custom p-5 text-center text-muted border-secondary">Aucune offre de stage ne correspond à vos critères de recherche.</div>
                </div>
            <?php else: ?>
                <?php foreach ($offres as $offre): 
                    $peut_modifier_cette_offre = ($offre['promotion_visee'] === $promo_prioritaire);
                    
                    $badge_color = "bg-primary";
                    $border_neon = "border-color: var(--bs-border-color);";
                    if ($offre['promotion_visee'] === 'MMI1') { $badge_color = "bg-purple"; $border_neon = "border: 1px solid #8a2be2;"; }
                    if ($offre['promotion_visee'] === 'MMI2') { $badge_color = "bg-info text-dark"; $border_neon = "border: 1px solid #0dcaf0;"; }
                    if ($offre['promotion_visee'] === 'MMI3') { $badge_color = "bg-success"; $border_neon = "border: 1px solid #198754;"; }
                    
                    if ($peut_modifier_cette_offre) {
                        $border_neon .= " box-shadow: 0 0 12px rgba(138, 43, 226, 0.35); border-width: 2px;";
                    }
                ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between shadow-sm" style="<?= $border_neon ?>">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold text-white m-0 text-truncate" style="max-width: 70%;"><?= htmlspecialchars($offre['intitule']) ?></h5>
                                    <div>
                                        <?php if ($peut_modifier_cette_offre): ?>
                                            <span class="badge bg-purple me-1 small"><i class="bi bi-pencil-square"></i> Gérée</span>
                                        <?php endif; ?>
                                        <span class="badge <?= $badge_color ?> font-monospace"><?= htmlspecialchars($offre['promotion_visee']) ?></span>
                                    </div>
                                </div>
                                <div class="small text-white-50 mb-3">
                                    <i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($offre['lieu'] ?? 'Non spécifié') ?>
                                    <span class="mx-2">|</span>
                                    <i class="bi bi-stopwatch text-info me-1"></i> <?= htmlspecialchars($offre['duree'] ?? 'Non spécifiée') ?>
                                </div>
                                <p class="small text-white-50 mb-3" style="text-align: justify; line-height: 1.5;"><?= nl2br(htmlspecialchars($offre['description'])) ?></p>
                                <?php if (!empty($offre['competences'])): ?>
                                    <div class="mb-3">
                                        <div class="small text-purple fw-bold mb-1">Tags / Compétences :</div>
                                        <p class="small text-light m-0 font-monospace"><em><?= htmlspecialchars($offre['competences']) ?></em></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="border-top border-secondary pt-3 mt-2 d-flex justify-content-between align-items-center small font-monospace">
                                <span class="text-success fw-bold"><i class="bi bi-cash-coin me-1"></i> <?= ($offre['remuneration'] > 0) ? number_format($offre['remuneration'], 2, ',', ' ') . ' €' : 'Gratification légale'; ?></span>
                                
                                <?php if ($peut_modifier_cette_offre): ?>
                                    <button class="btn btn-purple btn-sm py-0.5 px-2 font-sans-serif" data-bs-toggle="modal" data-bs-target="#modalModifierOffre<?= $offre['id_offre'] ?>"><i class="bi bi-gear-fill me-1"></i> Éditer</button>
                                <?php else: ?>
                                    <span class="text-muted-custom">Lecture seule</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- FENÊTRE MODALE DE MODIFICATION -->
                    <?php if ($peut_modifier_cette_offre): ?>
                    <div class="modal fade" id="modalModifierOffre<?= $offre['id_offre'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                                <div class="modal-header bg-intranet-dark border-secondary">
                                    <h5 class="modal-title fw-bold text-purple"><i class="bi bi-pencil-square me-2"></i>Modifier la fiche #<?= $offre['id_offre'] ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id_offre" value="<?= $offre['id_offre'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label small">Intitulé de la mission</label>
                                        <input type="text" name="intitule" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($offre['intitule']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Description</label>
                                        <textarea name="description" rows="4" class="form-control bg-dark text-white border-secondary" required><?= htmlspecialchars($offre['description']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Compétences / Mots-clés</label>
                                        <input type="text" name="competences" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($offre['competences']) ?>">
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small">Durée</label>
                                            <input type="text" name="duree" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($offre['duree']) ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Lieu</label>
                                            <input type="text" name="lieu" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($offre['lieu']) ?>" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label small">Gratification mensuelle (€)</label>
                                        <input type="number" step="0.01" name="remuneration" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($offre['remuneration']) ?>">
                                    </div>
                                </div>
                                <div class="modal-footer border-secondary d-flex justify-content-between">
                                    <button type="submit" name="action_supprimer_offre" class="btn btn-outline-danger btn-sm" onclick="return confirm('Confirmez-vous la suppression définitive de cette offre de stage ?');"><i class="bi bi-trash3 me-1"></i> Supprimer l'offre</button>
                                    <div>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" name="action_modifier_offre" class="btn btn-purple btn-sm">Sauvegarder</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- FENÊTRE MODALE D'AJOUT -->
    <?php if ($est_un_responsable): ?>
    <div class="modal fade" id="modalNouvelleOffre" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content bg-dark border-secondary text-white">
                <div class="modal-header bg-intranet-dark border-secondary">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-purple me-2"></i>Saisir une offre pour les <?= $promo_prioritaire ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Intitulé de la mission</label>
                        <input type="text" name="intitule" class="form-control bg-dark text-white border-secondary" required placeholder="Ex: Assistant UX/UI Designer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Description des tâches</label>
                        <textarea name="description" rows="4" class="form-control bg-dark text-white border-secondary" required placeholder="Détaillez les missions du stage..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Mots-clés / Compétences</label>
                        <input type="text" name="competences" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Illustrator, HTML, CSS">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Durée du stage</label>
                            <input type="text" name="duree" class="form-control bg-dark text-white border-secondary" required placeholder="Ex: 8 semaines">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Lieu géographique</label>
                            <input type="text" name="lieu" class="form-control bg-dark text-white border-secondary" required placeholder="Ex: Champs-sur-Marne (74)">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small">Gratification mensuelle (€)</label>
                        <input type="number" step="0.01" name="remuneration" class="form-control bg-dark text-white border-secondary" placeholder="Laissez vide si gratification minimale">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="action_creer_offre" class="btn btn-purple btn-sm">Publier l'offre</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <footer class="bg-black text-white py-2 border-top border-secondary mt-auto">
        <div class="container-fluid px-4"><p class="m-0 text-muted-custom" style="font-size: 0.85rem;">&copy; 2026 Université Gustave Eiffel - Tom Pelloile - Robin Maréchal - Emerick Angel</p></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>