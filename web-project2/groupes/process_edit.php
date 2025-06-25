<?php
session_start();
require_once "../gestion-module-ue/includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $id_unite_enseignement = mysqli_real_escape_string($conn, $_POST['id_unite_enseignement']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $numero = mysqli_real_escape_string($conn, $_POST['numero']);
    $effectif = mysqli_real_escape_string($conn, $_POST['effectif']);
    $annee_universitaire = mysqli_real_escape_string($conn, $_POST['annee_universitaire']);
    $semestre = mysqli_real_escape_string($conn, $_POST['semestre']);

    // Vérification que le groupe existe
    $check_query = "SELECT id FROM groupes WHERE id = '$id'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Mise à jour du groupe
        $query = "UPDATE groupes SET 
                    id_unite_enseignement = '$id_unite_enseignement',
                    type = '$type',
                    numero = '$numero',
                    effectif = '$effectif',
                    annee_universitaire = '$annee_universitaire',
                    semestre = '$semestre'
                 WHERE id = '$id'";

        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Le groupe a été modifié avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du groupe: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Groupe non trouvé.";
    }
} else {
    $_SESSION['error'] = "Méthode non autorisée.";
}

header("Location: index.php");
exit();
