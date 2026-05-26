<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

if (isset($_GET['id']) && isset($_GET['type']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type'];
    $action = $_GET['action'];
    $table = '';
    $cle_primaire = '';

    if ($type === 'etudiant') {
        $table = 'etudiant';
        $cle_primaire = 'id_etudiant';
    } elseif ($type === 'enseignant') {
        $table = 'enseignant';
        $cle_primaire = 'id_enseignant';
    } elseif ($type === 'maitre') {
        $table = 'maître_de_stage';
        $cle_primaire = 'id_maitre';
    }

    if (!empty($table)) {
        if ($action === 'accepter') {
            $stmt = $pdo->prepare("UPDATE `$table` SET valide = 1 WHERE `$cle_primaire` = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'refuser') {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$cle_primaire` = ?");
            $stmt->execute([$id]);
        }
    }
}

header("Location: ../php/dashboard-admin.php");
exit();