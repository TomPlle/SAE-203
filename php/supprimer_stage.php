<?php
session_start();

// 1. Sécurité : Seuls les enseignants connectés peuvent annuler/supprimer un stage
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../index.html");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    require_once 'config.php';

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Suppression du stage ciblé
        $stmt = $pdo->prepare("DELETE FROM stage WHERE id_stage = ?");
        $stmt->execute([$_GET['id']]);

        // Redirection vers l'interface des affectations avec un message de succès
        header("Location: ../prof/validation-stage.php?msg=supprime");
        exit();

    } catch (PDOException $e) {
        die("Erreur lors de la suppression du stage : " . $e->getMessage());
    }
} else {
    header("Location: ../prof/validation-stage.php");
    exit();
}