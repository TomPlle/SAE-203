<?php
// On charge les identifiants du fichier config.php
require_once 'config.php';

try {
    // Création de la connexion
    $bdd = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    
    // Configuration pour afficher proprement les erreurs SQL si vous vous trompez
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si la connexion échoue, le site s'arrête et affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>