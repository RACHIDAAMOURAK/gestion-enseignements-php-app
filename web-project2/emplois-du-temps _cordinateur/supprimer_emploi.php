<?php
require_once "includes/config.php";

// Permettre de recevoir l'ID soit par POST soit par GET
$id = null;
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
}

if ($id !== null) {
    // Vérifier si l'emploi du temps existe
    $query_check = "SELECT id FROM emplois_temps WHERE id = ?";
    $stmt = $conn->prepare($query_check);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Supprimer d'abord les séances associées
        $query_delete_seances = "DELETE FROM seances WHERE id_emploi_temps = ?";
        $stmt = $conn->prepare($query_delete_seances);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Puis supprimer l'emploi du temps
        $query_delete = "DELETE FROM emplois_temps WHERE id = ?";
        $stmt = $conn->prepare($query_delete);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Emploi du temps supprimé avec succès'];
            // Rediriger vers la page de gestion si c'est une requête GET
            if (isset($_GET['id'])) {
                header('Location: gerer_emplois.php?success=delete');
                exit;
            }
        } else {
            $response = ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Emploi du temps non trouvé'];
    }
} else {
    $response = ['success' => false, 'message' => 'ID non fourni'];
}

// Si c'est une requête AJAX ou en POST, renvoyer une réponse JSON
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode($response);
} 