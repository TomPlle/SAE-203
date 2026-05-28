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

$id_etudiant = $_SESSION['user']['id_etudiant'];
$nom_etudiant = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : 'Nom';
$prenom_etudiant = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : 'Prénom';

$message = "";
$message_type = "";

// 2. EXPORT SPREADSHEET (Excel / Google Sheets)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historique_demarches.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    
    fputcsv($output, ['Entreprise Cible', 'Date de l\'action', 'Étape / Action menée', 'Statut / Réponse']);
    
    $stmtExport = $pdo->prepare("SELECT entreprise_cible, date_contact, type_action, reponse FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC");
    $stmtExport->execute([$id_etudiant]);
    
    while ($row = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// 3. ENREGISTREMENT D'UNE NOUVELLE ÉTAPE (INSERTION STANDARD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_demarche'])) {
    $entreprise = trim($_POST['entreprise_cible']);
    $date_contact = $_POST['date_contact'];
    $type_action = trim($_POST['type_action']);
    $reponse = $_POST['reponse'];

    if (!empty($entreprise) && !empty($date_contact) && !empty($type_action) && !empty($reponse)) {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO historique (entreprise_cible, date_contact, type_action, reponse, id_etudiant) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$entreprise, $date_contact, $type_action, $reponse, $id_etudiant]);
            $message = "Nouvelle étape enregistrée avec succès !";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement : " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $message_type = "warning";
    }
}

// 4. RÉCUPÉRATION DU FLUX CHRONOLOGIQUE DES DÉMARCHES
$stmtList = $pdo->prepare("SELECT * FROM historique WHERE id_etudiant = ? ORDER BY date_contact DESC, id_recherche DESC");
$stmtList->execute([$id_etudiant]);
$demarches = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Démarches - Étudiant</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <div class="fw-bold text-uppercase" style="font-size: 1.1rem; letter-spacing: 1px;">GESTIONNAIRE DE STAGE</div>
                    <div class="text-muted-custom" style="font-size: 0.8rem; letter-spacing: 0.5px;">UNIVERSITÉ GUSTAVE EIFFEL</div>
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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="demarches-etudiant.php">
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

    <main class="container-fluid px-4 py-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0"><i class="bi bi-journal-text text-purple me-2"></i>Suivi historique de mes démarches</h2>
            <a href="?export=csv" class="btn btn-success fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Exporter vers Sheet / Excel</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show bg-dark text-white border-<?php echo $message_type; ?> mb-4" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card-custom p-4">
                    <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">
                        <i class="bi bi-plus-square text-purple me-2"></i>Déclarer une nouvelle étape
                    </h5>
                    <form action="demarches-etudiant.php" method="POST">
                        <div class="mb-3">
                            <label for="entreprise_cible" class="form-label small text-muted-custom">Nom de l'entreprise concernée *</label>
                            <input type="text" id="entreprise_cible" name="entreprise_cible" class="form-control" placeholder="Ex: Google, Thales, EDF..." required>
                        </div>

                        <div class="mb-3">
                            <label for="date_contact" class="form-label small text-muted-custom">Date de l'action / événement *</label>
                            <input type="date" id="date_contact" name="date_contact" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="type_action" class="form-label small text-muted-custom">Action menée ou Événement *</label>
                            <input type="text" id="type_action" name="type_action" class="form-control" placeholder="Ex: Envoi CV, Relance, Entretien avec RH..." required>
                        </div>

                        <div class="mb-4">
                            <label for="reponse" class="form-label small text-muted-custom">Statut associé à cette date *</label>
                            <select id="reponse" name="reponse" class="form-select" required>
                                <option value="En attente" selected>⏳ En attente de réponse</option>
                                <option value="Entretien prévu">📅 Entretien prévu</option>
                                <option value="Entretien">📅 Entretien passé</option>
                                <option value="Refusé">❌ Refusé</option>
                                <option value="Validé">✅ Validé / Convention signée</option>
                            </select>
                        </div>

                        <button type="submit" name="ajouter_demarche" class="btn btn-purple w-100 fw-bold text-uppercase py-2">
                            Ajouter à l'historique
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card-custom p-4">
                    <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">
                        <i class="bi bi-clock-history text-purple me-2"></i>Flux de recherche temporel
                    </h5>
                    
                    <?php if (empty($demarches)): ?>
                        <div class="text-center py-5 text-muted-custom">
                            <i class="bi bi-archive fs-1 mb-3 d-block"></i>Aucune démarche enregistrée.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle border-secondary m-0">
                                <thead>
                                    <tr class="text-muted-custom border-bottom border-secondary">
                                        <th>Entreprise</th>
                                        <th>Date de l'action</th>
                                        <th>Action / Étape franchie</th>
                                        <th class="text-end">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($demarches as $d): 
                                        $status_text = htmlspecialchars($d['reponse']);
                                        
                                        // Attribution de la classe Bootstrap de couleur du badge
                                        if ($d['reponse'] === 'Validé' || $d['reponse'] === 'Convention signée') {
                                            $badge_class = "bg-success text-white"; // Vert
                                        } elseif ($d['reponse'] === 'Refusé') {
                                            $badge_class = "bg-danger text-white"; // Rouge
                                        } elseif ($d['reponse'] === 'En attente') {
                                            $badge_class = "bg-warning text-dark"; // Jaune
                                        } elseif ($d['reponse'] === 'Entretien' || $d['reponse'] === 'Entretien prévu') {
                                            $badge_class = "bg-primary text-white"; // Bleu pour le suivi entretien
                                        } else {
                                            $badge_class = "bg-secondary text-white";
                                        }
                                    ?>
                                        <tr class="border-bottom border-secondary-subtle">
                                            <td class="fw-bold text-white"><?php echo htmlspecialchars($d['entreprise_cible']); ?></td>
                                            <td class="font-monospace text-info small"><i class="bi bi-calendar3 me-2"></i><?php echo date('d/m/Y', strtotime($d['date_contact'])); ?></td>
                                            <td><span class="text-light"><?php echo htmlspecialchars($d['type_action']); ?></span></td>
                                            <td class="text-end">
                                                <span class="badge <?php echo $badge_class; ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>