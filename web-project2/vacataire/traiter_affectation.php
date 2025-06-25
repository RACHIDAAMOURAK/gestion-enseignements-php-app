<?php
session_start();
require_once "../gestion-module-ue/includes/db.php";

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour effectuer cette action.";
    header('Location: ../login.php');
    exit;
}

// Vérifier que l'utilisateur a le rôle approprié
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordonnateur') {
    $_SESSION['error'] = "Vous n'avez pas les droits pour effectuer cette action.";
    header('Location: liste_ue_vacant.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        if (!isset($_POST['id_ue']) || !isset($_POST['id_vacataire'])) {
            throw new Exception("Données du formulaire incomplètes.");
        }

        $id_ue = $_POST['id_ue'];
        $id_vacataire = $_POST['id_vacataire'];
        $commentaire = isset($_POST['commentaire']) ? $_POST['commentaire'] : '';

        // Récupérer les informations de l'UE
        $stmt = $conn->prepare("
            SELECT uv.*, ue.id_departement 
            FROM unites_vacantes_vacataires uv
            JOIN unites_enseignement ue ON uv.id_unite_enseignement = ue.id
            WHERE uv.id = ?
        ");
        $stmt->bind_param("i", $id_ue);
        $stmt->execute();
        $result = $stmt->get_result();
        $ue = $result->fetch_assoc();

        if (!$ue) {
            throw new Exception("L'UE sélectionnée n'existe pas.");
        }

        // Vérifier que le vacataire existe et est actif
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ? AND role = 'vacataire' AND actif = 1");
        $stmt->bind_param("i", $id_vacataire);
        $stmt->execute();
        $result = $stmt->get_result();
        $vacataire = $result->fetch_assoc();

        if (!$vacataire) {
            throw new Exception("Le vacataire sélectionné n'existe pas ou n'est pas actif.");
        }

        // Commencer la transaction
        $conn->begin_transaction();

        // Insérer dans l'historique des affectations
        $sql = "INSERT INTO historique_affectations_vacataire 
                (id_vacataire, id_unite_enseignement, date_affectation, action, commentaire, id_coordonnateur) 
                VALUES (?, ?, NOW(), 'affectation', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $id_vacataire, $ue['id_unite_enseignement'], $commentaire, $_SESSION['user_id']);
        $stmt->execute();

        // Supprimer l'UE de la table des UEs vacantes
        $sql = "DELETE FROM unites_vacantes_vacataires WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_ue);
        $stmt->execute();

        // Valider la transaction
        $conn->commit();

        $_SESSION['message'] = "L'UE a été affectée avec succès au vacataire.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        if (isset($conn) && $conn->connect_error === false) {
            $conn->rollback();
        }
        $_SESSION['message'] = "Erreur lors de l'affectation : " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Méthode non autorisée.";
    $_SESSION['message_type'] = "danger";
}

// Rediriger vers la page de liste
header('Location: liste_ue_vacant.php');
exit;
?> 