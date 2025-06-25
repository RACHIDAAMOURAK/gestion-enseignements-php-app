<?php
session_start();
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Vérifier si l'UE existe
    $check_query = "SELECT code FROM unites_enseignement WHERE id = '$id'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $ue = mysqli_fetch_assoc($check_result);
        
        // Supprimer d'abord les enregistrements liés dans historique_affectations_vacataire
        $delete_historique = "DELETE FROM historique_affectations_vacataire WHERE id_unite_enseignement = '$id'";
        mysqli_query($conn, $delete_historique);
        
        // Supprimer l'UE
        $delete_query = "DELETE FROM unites_enseignement WHERE id = '$id'";
        
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success'] = "L'UE " . htmlspecialchars($ue['code']) . " a été supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de l'UE : " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "UE non trouvée.";
    }
} else {
    $_SESSION['error'] = "ID non spécifié.";
}

header("Location: index.php");
exit();
?>