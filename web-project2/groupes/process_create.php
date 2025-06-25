<?php
require_once '../gestion-module-ue/includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $id_unite_enseignement = mysqli_real_escape_string($conn, $_POST['id_unite_enseignement']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $numero = mysqli_real_escape_string($conn, $_POST['numero']);
    $effectif = mysqli_real_escape_string($conn, $_POST['effectif']);
    $annee_universitaire = mysqli_real_escape_string($conn, $_POST['annee_universitaire']);
    $semestre = mysqli_real_escape_string($conn, $_POST['semestre']);

    // Vérifier que l'UE est associée à la filière
    $check_query = "SELECT 1 FROM unites_enseignement 
                    WHERE id = ? AND id_filiere = ?";
    
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $id_unite_enseignement, $id_filiere);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Vérifier si un groupe avec le même numéro existe déjà
        $check_duplicate = "SELECT 1 FROM groupes 
                          WHERE type = ? 
                          AND numero = ? 
                          AND id_unite_enseignement = ?
                          AND id_filiere = ?
                          AND annee_universitaire = ?
                          AND semestre = ?";
        
        $stmt_duplicate = mysqli_prepare($conn, $check_duplicate);
        mysqli_stmt_bind_param($stmt_duplicate, "siiisi", $type, $numero, $id_unite_enseignement, $id_filiere, $annee_universitaire, $semestre);
        mysqli_stmt_execute($stmt_duplicate);
        mysqli_stmt_store_result($stmt_duplicate);

        if (mysqli_stmt_num_rows($stmt_duplicate) === 0) {
            // Insérer le nouveau groupe
            $insert_query = "INSERT INTO groupes (
                type, 
                numero, 
                id_unite_enseignement,
                id_filiere,
                effectif, 
                annee_universitaire, 
                semestre
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt_insert, "siiisis", 
                $type, 
                $numero, 
                $id_unite_enseignement,
                $id_filiere,
                $effectif,
                $annee_universitaire,
                $semestre
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['success'] = "Le groupe a été créé avec succès.";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de la création du groupe : " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Un groupe avec ces caractéristiques existe déjà pour cette filière.";
        }
    } else {
        $_SESSION['error'] = "L'UE sélectionnée n'est pas associée à cette filière.";
    }
} else {
    $_SESSION['error'] = "Méthode non autorisée.";
}

header("Location: create.php");
exit();
?> 