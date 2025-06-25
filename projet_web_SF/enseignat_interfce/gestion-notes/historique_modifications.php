<?php
require_once 'includes/config.php';

// Récupération de l'historique des modifications
$stmt = $conn->prepare("
    SELECT 
        n.date_modification,
        n.note,
        e.numero_etudiant,
        e.nom as nom_etudiant,
        e.prenom as prenom_etudiant,
        ue.code as code_ue,
        ue.intitule as nom_ue,
        u.nom as nom_enseignant,
        u.prenom as prenom_enseignant,
        n.type_session
    FROM notes n
    JOIN etudiants e ON n.id_etudiant = e.id
    JOIN unites_enseignement ue ON n.id_unite_enseignement = ue.id
    JOIN utilisateurs u ON n.id_enseignant = u.id
    WHERE n.date_modification IS NOT NULL
    ORDER BY n.date_modification DESC
");
$stmt->execute();
$modifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Modifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Historique des Modifications de Notes</h1>
        
        <div class="card mt-4">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date de modification</th>
                            <th>UE</th>
                            <th>Session</th>
                            <th>Étudiant</th>
                            <th>Note</th>
                            <th>Modifié par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modifications as $modif): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($modif['date_modification'])); ?></td>
                            <td><?php echo htmlspecialchars($modif['code_ue'] . ' - ' . $modif['nom_ue']); ?></td>
                            <td><?php echo ucfirst($modif['type_session']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($modif['numero_etudiant'] . ' - ' . 
                                                          $modif['nom_etudiant'] . ' ' . 
                                                          $modif['prenom_etudiant']); ?>
                            </td>
                            <td><?php echo number_format($modif['note'], 2); ?></td>
                            <td>
                                <?php echo htmlspecialchars($modif['prenom_enseignant'] . ' ' . 
                                                          $modif['nom_enseignant']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html> 