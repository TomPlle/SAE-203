<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: ../index.html");
    exit();
}

switch ($_SESSION['role']) {
    case 'admin':
        header("Location: ../admin/dashboard-admin.php");
        break;
    case 'etudiant':
        header("Location: ../etudiant/accueil-etudiant.php");
        break;
    case 'prof':
        header("Location: ../enseignant/accueil-enseignant.php");
        break;
    default:
        header("Location: ../index.html");
        break;
}
exit();
?>