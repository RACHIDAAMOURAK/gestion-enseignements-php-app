<?php
require_once "includes/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['id', 'id_filiere', 'semestre', 'annee_universitaire', 'date_debut', 'date_fin', 'statut'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // Handle fichier_path separately since it can be empty
    $fichier_path = isset($_POST['fichier_path']) ? $_POST['fichier_path'] : '';
    
    if (!empty($missing_fields)) {
        $_SESSION['error'] = 'Champs manquants: ' . implode(', ', $missing_fields);
        header('Location: gerer_emplois.php');
        exit;
    } else {
        $id = intval($_POST['id']);
        $id_filiere = intval($_POST['id_filiere']);
        $semestre = $_POST['semestre'];
        $annee_universitaire = $_POST['annee_universitaire'];
        $date_debut = $_POST['date_debut'];
        $date_fin = $_POST['date_fin'];
        $statut = $_POST['statut'];
        
        // Vérifier si l'emploi du temps existe
        $query_check = "SELECT id FROM emplois_temps WHERE id = ?";
        $stmt = $conn->prepare($query_check);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Vérifier si la filière existe
            $query_check_filiere = "SELECT id FROM filieres WHERE id = ?";
            $stmt = $conn->prepare($query_check_filiere);
            $stmt->bind_param("i", $id_filiere);
            $stmt->execute();
            $result_filiere = $stmt->get_result();
            
            if ($result_filiere->num_rows > 0) {
                // Mettre à jour l'emploi du temps
                $query_update = "UPDATE emplois_temps 
                                SET id_filiere = ?, 
                                    semestre = ?, 
                                    annee_universitaire = ?, 
                                    date_debut = ?, 
                                    date_fin = ?, 
                                    fichier_path = ?,
                                    statut = ?,
                                    date_modification = NOW()
                                WHERE id = ?";
                
                $stmt = $conn->prepare($query_update);
                $stmt->bind_param("iisssssi", 
                    $id_filiere, 
                    $semestre, 
                    $annee_universitaire, 
                    $date_debut, 
                    $date_fin, 
                    $fichier_path,
                    $statut,
                    $id
                );
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Emploi du temps modifié avec succès';
                    header('Location: gerer_emplois.php');
                    exit;
                } else {
                    $_SESSION['error'] = 'Erreur lors de la modification: ' . $conn->error;
                    header('Location: gerer_emplois.php');
                    exit;
                }
            } else {
                $_SESSION['error'] = 'La filière sélectionnée n\'existe pas';
                header('Location: gerer_emplois.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Emploi du temps non trouvé';
            header('Location: gerer_emplois.php');
            exit;
        }
    }
} else {
    $_SESSION['error'] = 'Méthode non autorisée';
    header('Location: gerer_emplois.php');
    exit;
} 