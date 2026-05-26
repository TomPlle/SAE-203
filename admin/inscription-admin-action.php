<?php
// On inclut le fichier de configuration global (ajustez le chemin si nécessaire)
require_once '../php/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $tel = !empty($_POST['tel']) ? htmlspecialchars($_POST['tel']) : null;
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Vérification de sécurité sur les mots de passe
    if ($password !== $confirm_password) {
        die("Erreur : Les mots de passe ne correspondent pas.");
    }

    // 2. Vérification si l'email existe déjà dans la table admin
    $stmtCheck = $pdo->prepare("SELECT id_admin FROM admin WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        die("Erreur : Cette adresse email est déjà associée à un compte administrateur.");
    }

    // 3. Hachage du mot de passe (exactement comme pour les étudiants et professeurs)
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // 4. Insertion dans la table 'admin'
    try {
        $stmt = $pdo->prepare("INSERT INTO admin (nom, prenom, email, password, tel) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $passwordHash, $tel]);
        
        // Redirection vers la page de connexion avec un indicateur de succès
        header("Location: ../index.html?success=admin_created");
        exit();
    } catch (PDOException $e) {
        die("Erreur lors de la création du compte : " . $e->getMessage());
    }
} else {
    // Si le script est appelé hors POST, on redirige
    header("Location: creer-admin.html");
    exit();
}