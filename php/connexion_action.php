<?php
session_start();
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    // 1. Chercher dans Admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'admin';
        header("Location: ../admin/dashboard-admin.php");
        exit();
    }

    // 2. Chercher dans Etudiant
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['valide'] == 0) { die("Votre compte étudiant est en attente de validation par un administrateur."); }
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'etudiant';
        header("Location: ../etudiant/accueil-etudiant.php");
        exit();
    }

    // 3. Chercher dans Enseignant
    $stmt = $pdo->prepare("SELECT * FROM enseignant WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['valide'] == 0) { die("Votre compte enseignant est en attente de validation."); }
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'enseignant';
        header("Location: ../enseignant/accueil-enseignant.php"); // À créer si besoin
        exit();
    }

    // 4. Chercher dans Maître de stage
    $stmt = $pdo->prepare("SELECT * FROM maître_de_stage WHERE email_pro = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['valide'] == 0) { die("Votre compte maître de stage est en attente de validation."); }
        $_SESSION['user'] = $user;
        $_SESSION['role'] = 'maitre';
        header("Location: ../dashboard-maitre.html"); // À créer si besoin
        exit();
    }

    // Si aucun utilisateur n'est trouvé
    header("Location: ../index.html?error=bad_credentials");
    exit();
}
?>