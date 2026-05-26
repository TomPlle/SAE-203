<?php
// 1. Initialiser ou récupérer la session existante
session_start();

// 2. Vider toutes les variables de session ($_SESSION['user'], $_SESSION['role'], etc.)
$_SESSION = array();

// 3. Si un cookie de session existe, le détruire également (bonne pratique de sécurité)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Détruire complètement la session sur le serveur
session_destroy();

// 5. Rediriger l'utilisateur vers la page de connexion à la racine du site
header("Location: ../index.html");
exit();
?>