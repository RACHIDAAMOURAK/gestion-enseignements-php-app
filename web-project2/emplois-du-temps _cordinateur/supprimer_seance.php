<?php
require_once "../gestion-module-ue/includes/db.php";

// Vérification de l'ID de la séance
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_seance = intval($_GET['id']);

// Récupération des données de la séance pour vérifier les droits
$query_seance = "SELECT id_enseignant FROM seances WHERE id = ?";
$stmt_seance = mysqli_prepare($conn, $query_seance);
mysqli_stmt_bind_param($stmt_seance, "i", $id_seance);
mysqli_stmt_execute($stmt_seance);
$result_seance = mysqli_stmt_get_result($stmt_seance);

if (!$result_seance || mysqli_num_rows($result_seance) === 0) {
    header("Location: index.php");
    exit();
}

$seance = mysqli_fetch_assoc($result_seance);

// Vérification des droits (seul l'enseignant concerné ou un admin peut supprimer)
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $seance['id_enseignant']) {
    header("Location: index.php");
    exit();
}

// Suppression de la séance
$query = "DELETE FROM seances WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_seance);

if (mysqli_stmt_execute($stmt)) {
    header("Location: index.php?success=1");
} else {
    header("Location: index.php?error=" . urlencode("Erreur lors de la suppression : " . mysqli_error($conn)));
}
?> 