<?php
session_start();
require_once 'includes/db.php';

// Vérifier si l'utilisateur est un coordonnateur
if ($_SESSION['role'] !== 'coordonnateur') {
    $_SESSION['error'] = "Seuls les coordonnateurs peuvent créer des unités d'enseignement.";
    header('Location: index.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et nettoyage des données du formulaire
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $intitule = mysqli_real_escape_string($conn, $_POST['intitule']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);
    $id_departement = mysqli_real_escape_string($conn, $_POST['id_departement']);
    $specialite = mysqli_real_escape_string($conn, $_POST['specialite']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_responsable = !empty($_POST['id_responsable']) ? mysqli_real_escape_string($conn, $_POST['id_responsable']) : null;
    $statut = mysqli_real_escape_string($conn, $_POST['statut']);
    $semestre = (int)$_POST['semestre'];
    $annee_universitaire = mysqli_real_escape_string($conn, $_POST['annee_universitaire']);
    $volume_horaire_cm = (float)$_POST['volume_horaire_cm'];
    $volume_horaire_td = (float)($_POST['volume_horaire_td'] ?? 0);
    $volume_horaire_tp = (float)($_POST['volume_horaire_tp'] ?? 0);

    // Vérification du code UE (format: 2 lettres majuscules suivies de 3 chiffres)
    if (!preg_match('/^[A-Z]{2}\d{3}$/', $code)) {
        $_SESSION['error'] = "Le format du code UE est invalide. Le format doit être : 2 lettres majuscules suivies de 3 chiffres (exemple: IN101, MA201, PH301)";
        header('Location: create.php');
        exit;
    }

    // Vérification si le code existe déjà
    $check_query = "SELECT id FROM unites_enseignement WHERE code = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['error'] = "Ce code UE existe déjà";
        header('Location: create.php');
        exit;
    }
    mysqli_stmt_close($stmt);

    // Démarrer la transaction
    mysqli_begin_transaction($conn);

    try {
        // Préparation de la requête d'insertion pour l'UE
        $sql = "INSERT INTO unites_enseignement (
            code, 
            intitule, 
            description, 
            credits,
            id_departement,
            specialite,
            id_filiere,
            semestre,
            volume_horaire_cm,
            volume_horaire_td,
            volume_horaire_tp,
            annee_universitaire,
            id_responsable,
            statut
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 
            "ssssissidddsis",
            $code,
            $intitule,
            $description,
            $credits,
            $id_departement,
            $specialite,
            $id_filiere,
            $semestre,
            $volume_horaire_cm,
            $volume_horaire_td,
            $volume_horaire_tp,
            $annee_universitaire,
            $id_responsable,
            $statut
        );

        if (mysqli_stmt_execute($stmt)) {
            // Valider la transaction
            mysqli_commit($conn);
            $_SESSION['success'] = "L'unité d'enseignement a été créée avec succès";
            header('Location: index.php');
            exit;
        } else {
            throw new Exception("Erreur lors de la création de l'unité d'enseignement : " . mysqli_error($conn));
        }

    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = "Erreur lors de la création de l'UE : " . $e->getMessage();
        header('Location: create.php');
        exit;
    }

    mysqli_stmt_close($stmt);
} else {
    header('Location: create.php');
    exit;
}

mysqli_close($conn);
?>