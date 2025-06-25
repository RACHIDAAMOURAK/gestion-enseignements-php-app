<?php
require_once 'includes/config.php';

try {
    $stmt = $conn->query('SELECT id, numero_etudiant, nom, prenom FROM etudiants');
    echo "Liste des étudiants disponibles :\n\n";
    echo "ID | Numéro étudiant | Nom | Prénom\n";
    echo "----------------------------------------\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . " | " . $row['numero_etudiant'] . " | " . $row['nom'] . " | " . $row['prenom'] . "\n";
    }
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 