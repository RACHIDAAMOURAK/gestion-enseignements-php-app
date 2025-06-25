<?php
require_once 'includes/config.php';

try {
    $stmt = $conn->query('
        SELECT 
            ha.id,
            ha.id_utilisateur,
            u.nom as nom_utilisateur,
            u.prenom as prenom_utilisateur,
            ha.role,
            ue.id as id_ue,
            ue.code as code_ue,
            ue.intitule as intitule_ue
        FROM historique_affectations ha
        JOIN utilisateurs u ON ha.id_utilisateur = u.id
        JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
        WHERE ha.role = "enseignant"
        ORDER BY ue.code, ha.id DESC
    ');
    
    echo "Liste complète des affectations enseignants-UE :\n\n";
    echo "ID Affectation | ID Enseignant | Nom Enseignant | UE (ID) | Code UE | Intitulé\n";
    echo "-------------------------------------------------------------------------\n";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . " | " . 
             $row['id_utilisateur'] . " | " . 
             $row['nom_utilisateur'] . " " . $row['prenom_utilisateur'] . " | " .
             $row['id_ue'] . " | " .
             $row['code_ue'] . " | " .
             $row['intitule_ue'] . "\n";
    }
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 