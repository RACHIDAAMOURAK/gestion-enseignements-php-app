<?php
// Configuration de la connexion à la base de données
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

try {
    // Connexion à MySQL
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }
    
    echo "<h2>Groupes disponibles dans la base de données</h2>";
    
    // Requête pour récupérer tous les groupes
    $query = "SELECT id, type, numero, id_filiere, id_unite_enseignement FROM groupes";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Type</th><th>Numéro</th><th>ID Filière</th><th>ID UE</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['type'] . "</td>";
            echo "<td>" . $row['numero'] . "</td>";
            echo "<td>" . $row['id_filiere'] . "</td>";
            echo "<td>" . $row['id_unite_enseignement'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun groupe trouvé dans la base de données.</p>";
    }
    
    echo "<h2>Séances avec références aux groupes</h2>";
    
    // Vérifier les séances qui référencent des groupes
    $query = "SELECT s.id, s.jour, s.heure_debut, s.heure_fin, s.type, s.id_groupe, 
              g.id as groupe_id, g.type as groupe_type, g.numero as groupe_numero
              FROM seances s
              LEFT JOIN groupes g ON s.id_groupe = g.id
              WHERE s.id_groupe IS NOT NULL";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Séance</th><th>Jour</th><th>Horaires</th><th>Type Séance</th><th>ID Groupe</th><th>Groupe trouvé</th><th>Type Groupe</th><th>Numéro Groupe</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['jour'] . "</td>";
            echo "<td>" . $row['heure_debut'] . " - " . $row['heure_fin'] . "</td>";
            echo "<td>" . $row['type'] . "</td>";
            echo "<td>" . $row['id_groupe'] . "</td>";
            echo "<td>" . ($row['groupe_id'] ? "Oui" : "Non") . "</td>";
            echo "<td>" . ($row['groupe_type'] ?? "N/A") . "</td>";
            echo "<td>" . ($row['groupe_numero'] ?? "N/A") . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucune séance avec référence à des groupes trouvée.</p>";
    }
    
    // Fermer la connexion
    $conn->close();
    
} catch (Exception $e) {
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
}
?>