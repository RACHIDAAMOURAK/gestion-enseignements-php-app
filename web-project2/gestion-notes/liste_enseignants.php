<?php
require_once 'includes/config.php';

try {
    $stmt = $conn->query('SELECT id, nom, prenom, email FROM utilisateurs WHERE role = "enseignant"');
    echo "Liste des enseignants disponibles :\n\n";
    echo "ID | Nom | Prénom | Email\n";
    echo "----------------------------------------\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . " | " . $row['nom'] . " | " . $row['prenom'] . " | " . $row['email'] . "\n";
    }
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 