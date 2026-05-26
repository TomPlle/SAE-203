<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Sécurisation du mot de passe
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'etudiant') {
        $matricule = htmlspecialchars($_POST['matricule']);
        $tel = htmlspecialchars($_POST['tel']);
        $date_naiss = $_POST['date_naiss'];
        $adresse = htmlspecialchars($_POST['adresse']);
        $promo = htmlspecialchars($_POST['promo']);
        $gp_td = htmlspecialchars($_POST['gp_td']);
        $gp_tp = htmlspecialchars($_POST['gp_tp']);

        $stmt = $pdo->prepare("INSERT INTO etudiant (matricule, nom, prenom, email, password, tel, date_naiss, adresse, promo, gp_td, gp_tp, valide) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$matricule, $nom, $prenom, $email, $passwordHash, $tel, $date_naiss, $adresse, $promo, $gp_td, $gp_tp]);

    } elseif ($role === 'enseignant') {
        $role_enseignant = htmlspecialchars($_POST['role_enseignant']);

        $stmt = $pdo->prepare("INSERT INTO enseignant (nom, prenom, email, password, role, valide) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nom, $prenom, $email, $passwordHash, $role_enseignant]);

    } elseif ($role === 'responsable') {
        $tel = htmlspecialchars($_POST['tel']);
        $email_pro = htmlspecialchars($_POST['email_pro']);
        
        // Note: id_entreprise est NOT NULL dans votre BDD. Ici on met 1 par défaut, à adapter selon votre logique d'entreprise
        $id_entreprise = 1; 

        $stmt = $pdo->prepare("INSERT INTO responsable_de_stage (nom, prenom, tel, email_pro, password, id_entreprise, valide) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nom, $prenom, $tel, $email_pro, $passwordHash, $id_entreprise]);
    }

    // Redirection vers la page de connexion avec un message de succès
    header("Location: ../index.html?success=account_created");
    exit();
}
?>