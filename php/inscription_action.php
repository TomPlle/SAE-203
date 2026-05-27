<?php
// À ENLEVER EN PRODUCTION - Permet d'afficher l'erreur exacte si ça plante
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        die("Les mots de passe ne correspondent pas.");
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'etudiant') {
        $matricule = !empty($_POST['matricule']) ? htmlspecialchars($_POST['matricule']) : null;
        $tel = !empty($_POST['tel_etudiant']) ? htmlspecialchars($_POST['tel_etudiant']) : null; // <--- CORRIGÉ
        $date_naiss = !empty($_POST['date_naiss']) ? $_POST['date_naiss'] : null;
        $adresse = !empty($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : null;
        $promo = !empty($_POST['promo']) ? htmlspecialchars($_POST['promo']) : null;
        $gp_td = !empty($_POST['gp_td']) ? htmlspecialchars($_POST['gp_td']) : null;
        $gp_tp = !empty($_POST['gp_tp']) ? htmlspecialchars($_POST['gp_tp']) : null;

        $stmt = $pdo->prepare("INSERT INTO etudiant (matricule, nom, prenom, email, password, tel, date_naiss, adresse, promo, gp_td, gp_tp, valide) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$matricule, $nom, $prenom, $email, $passwordHash, $tel, $date_naiss, $adresse, $promo, $gp_td, $gp_tp]);

    } elseif ($role === 'enseignant') {
        $role_enseignant = !empty($_POST['role_enseignant']) ? htmlspecialchars($_POST['role_enseignant']) : null;

        $stmt = $pdo->prepare("INSERT INTO enseignant (nom, prenom, email, password, role, valide) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nom, $prenom, $email, $passwordHash, $role_enseignant]);

    } elseif ($role === 'maitre') {
        $tel = !empty($_POST['tel_maitre']) ? htmlspecialchars($_POST['tel_maitre']) : null; // <--- CORRIGÉ
        $email_pro = !empty($_POST['email_pro']) ? htmlspecialchars($_POST['email_pro']) : null;
        
        $id_entreprise = 1; 

        $stmt = $pdo->prepare("INSERT INTO responsable_de_stage (nom, prenom, tel, email_pro, password, id_entreprise, valide) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nom, $prenom, $tel, $email_pro, $passwordHash, $id_entreprise]);
    } else {
        die("Rôle non valide.");
    }

    header("Location: ../index.html?success=account_created");
    exit();
}
?>