<?php
session_start();
require_once "../gestion-module-ue/includes/db.php";

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Vérifier si le groupe existe
    $check_query = "SELECT id FROM groupes WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Supprimer le groupe
        $delete_query = "DELETE FROM groupes WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt_delete, "i", $id);

        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['success'] = "Le groupe a été supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du groupe : " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Le groupe n'existe pas.";
    }
} else {
    $_SESSION['error'] = "ID du groupe non spécifié.";
}

header("Location: index.php");
exit();
