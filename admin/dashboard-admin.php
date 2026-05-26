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

// 2. Récupération exhaustive des informations (SANS le champ password)
// Étudiants en attente
$reqEtudiants = $pdo->query("SELECT id_etudiant, matricule, nom, prenom, email, tel, date_naiss, adresse, promo, gp_td, gp_tp FROM etudiant WHERE valide = 0");
$etudiants = $reqEtudiants->fetchAll(PDO::FETCH_ASSOC);

// Enseignants en attente
$reqEnseignants = $pdo->query("SELECT id_enseignant, nom, prenom, email, role FROM enseignant WHERE valide = 0");
$enseignants = $reqEnseignants->fetchAll(PDO::FETCH_ASSOC);

// Maîtres de stage en attente
$reqMaitres = $pdo->query("SELECT m.id_responsable AS id_maitre, m.nom, m.prenom, m.tel, m.email_pro, e.nom_societe FROM responsable_de_stage m LEFT JOIN entreprise e ON m.id_entreprise = e.id_entreprise WHERE m.valide = 0");
$maitres = $reqMaitres->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des Comptes - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" type="image/png" href="../images/logo-noir-blanc.png">
</head>
<body class="d-flex flex-column min-vh-100 bg-dark text-white">

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
                        <a class="nav-link nav-link-custom active d-flex align-items-center" href="dashboard-admin.php">
                            <i class="bi bi-speedometer2 me-2 fs-4"></i> Vue globale
                        </a>
                    </li>
                    <li class="nav-item border-start border-secondary">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="suivi-etudiant.php">
                            <i class="bi bi-person-lines-fill me-2 fs-4"></i> Suivi Étudiants
                        </a>
                    </li>
                </ul>
                <div class="ms-2 pe-3">
                    <a href="../php/deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5 flex-grow-1">
        <div class="mb-4">
            <h2 class="fw-bold text-purple"><i class="bi bi-shield-check me-2"></i>Comptes en attente d'approbation</h2>
            <p class="text-muted-custom small">Validez ou refusez les demandes d'accès aux différents espaces de l'application.</p>
        </div>

        <ul class="nav nav-tabs border-secondary mb-4" id="validationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active text-white border-secondary bg-transparent fw-bold" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">
                    Étudiants <span class="badge bg-purple ms-1"><?= count($etudiants) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-white border-secondary bg-transparent fw-bold" id="teachers-tab" data-bs-toggle="tab" data-bs-target="#teachers" type="button" role="tab">
                    Enseignants <span class="badge bg-purple ms-1"><?= count($enseignants) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-white border-secondary bg-transparent fw-bold" id="tutors-tab" data-bs-toggle="tab" data-bs-target="#tutors" type="button" role="tab">
                    Maîtres de Stage <span class="badge bg-purple ms-1"><?= count($maitres) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="validationTabsContent">
            
            <div class="tab-pane fade show active" id="students" role="tabpanel">
                <?php if (empty($etudiants)): ?>
                    <div class="card-custom p-4 text-center text-muted-custom small">
                        <i class="bi bi-info-circle me-2"></i>Aucune demande d'inscription d'étudiant.
                    </div>
                <?php else: ?>
                    <div class="card-custom p-4">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped align-middle m-0">
                                <thead>
                                    <tr class="text-muted-custom">
                                        <th>Matricule</th>
                                        <th>Nom / Prénom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Date de Naiss.</th>
                                        <th>Adresse</th>
                                        <th>Promo / Groupes</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiants as $e): ?>
                                        <tr>
                                            <td><code class="text-info"><?= htmlspecialchars($e['matricule']) ?></code></td>
                                            <td class="fw-bold"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></td>
                                            <td><?= htmlspecialchars($e['email']) ?></td>
                                            <td><?= htmlspecialchars($e['tel'] ?? 'Non renseigné') ?></td>
                                            <td><?= $e['date_naiss'] ? date('d/m/Y', strtotime($e['date_naiss'])) : 'Non renseignée' ?></td>
                                            <td class="small text-muted-custom"><?= htmlspecialchars($e['adresse'] ?? 'Non renseignée') ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($e['promo']) ?></span>
                                                <span class="badge bg-dark border border-secondary">TD: <?= htmlspecialchars($e['gp_td']) ?></span>
                                                <span class="badge bg-dark border border-secondary">TP: <?= htmlspecialchars($e['gp_tp']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="../php/valider_compte.php?id=<?= $e['id_etudiant'] ?>&type=etudiant&action=accepter" class="btn btn-sm btn-success" title="Accepter"><i class="bi bi-check-lg"></i></a>
                                                    <a href="../php/valider_compte.php?id=<?= $e['id_etudiant'] ?>&type=etudiant&action=refuser" class="btn btn-sm btn-danger" onclick="return confirm('Refuser cette inscription ?');" title="Refuser"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="teachers" role="tabpanel">
                <?php if (empty($enseignants)): ?>
                    <div class="card-custom p-4 text-center text-muted-custom small">
                        <i class="bi bi-info-circle me-2"></i>Aucune demande d'inscription d'enseignant.
                    </div>
                <?php else: ?>
                    <div class="card-custom p-4">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped align-middle m-0">
                                <thead>
                                    <tr class="text-muted-custom">
                                        <th>Nom / Prénom</th>
                                        <th>Email</th>
                                        <th>Rôle / Statut enseignant</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enseignants as $t): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?></td>
                                            <td><?= htmlspecialchars($t['email']) ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($t['role']) ?></span></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="../php/valider_compte.php?id=<?= $t['id_enseignant'] ?>&type=enseignant&action=accepter" class="btn btn-sm btn-success" title="Accepter"><i class="bi bi-check-lg"></i></a>
                                                    <a href="../php/valider_compte.php?id=<?= $t['id_enseignant'] ?>&type=enseignant&action=refuser" class="btn btn-sm btn-danger" onclick="return confirm('Refuser cette inscription ?');" title="Refuser"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tutors" role="tabpanel">
                <?php if (empty($maitres)): ?>
                    <div class="card-custom p-4 text-center text-muted-custom small">
                        <i class="bi bi-info-circle me-2"></i>Aucune demande d'inscription de maître de stage.
                    </div>
                <?php else: ?>
                    <div class="card-custom p-4">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped align-middle m-0">
                                <thead>
                                    <tr class="text-muted-custom">
                                        <th>Nom / Prénom</th>
                                        <th>Email Professionnel</th>
                                        <th>Téléphone</th>
                                        <th>Entreprise</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maitres as $m): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></td>
                                            <td><?= htmlspecialchars($m['email_pro']) ?></td>
                                            <td><?= htmlspecialchars($m['tel'] ?? 'Non renseigné') ?></td>
                                            <td><i class="bi bi-building"></i> <?= htmlspecialchars($m['nom_societe'] ?? 'ID Entreprise: n°1') ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="../php/valider_compte.php?id=<?= $m['id_maitre'] ?>&type=maitre&action=accepter" class="btn btn-sm btn-success" title="Accepter"><i class="bi bi-check-lg"></i></a>
                                                    <a href="../php/valider_compte.php?id=<?= $m['id_maitre'] ?>&type=maitre&action=refuser" class="btn btn-sm btn-danger" onclick="return confirm('Refuser cette inscription ?');" title="Refuser"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
</html>