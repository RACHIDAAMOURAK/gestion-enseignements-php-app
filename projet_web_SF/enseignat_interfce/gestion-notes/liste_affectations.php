<?php
require_once 'includes/config.php';

try {
    $stmt = $conn->query('
        SELECT 
            ae.id_enseignant,
            u.nom as nom_enseignant,
            u.prenom as prenom_enseignant,
            ue.id as id_ue,
            ue.code as code_ue,
            ue.intitule as intitule_ue
        FROM affectation_enseignants ae
        JOIN utilisateurs u ON ae.id_enseignant = u.id
        JOIN unites_enseignement ue ON ae.id_unite_enseignement = ue.id
        ORDER BY ue.code
    ');
    
    echo "Liste des affectations enseignants-UE :\n\n";
    echo "ID Enseignant | Nom Enseignant | UE (ID) | Code UE | Intitulé\n";
    echo "----------------------------------------------------------------\n";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id_enseignant'] . " | " . 
             $row['nom_enseignant'] . " " . $row['prenom_enseignant'] . " | " .
             $row['id_ue'] . " | " .
             $row['code_ue'] . " | " .
             $row['intitule_ue'] . "\n";
    }
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 