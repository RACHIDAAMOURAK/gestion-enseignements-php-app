<?php
require_once 'includes/config.php';

if (!isset($_GET['fichier'])) {
    header('Location: index.php');
    exit;
}

$id_fichier = $_GET['fichier'];

// Récupération des informations du fichier
$stmt = $conn->prepare("
    SELECT fn.*, ue.code as code_ue, ue.intitule as nom_ue
    FROM fichiers_notes fn
    JOIN unites_enseignement ue ON fn.id_unite_enseignement = ue.id
    WHERE fn.id = ?
");
$stmt->execute([$id_fichier]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fichier) {
    header('Location: index.php');
    exit;
}

// Récupération des notes
$stmt = $conn->prepare("
    SELECT n.*, e.numero_etudiant, e.nom, e.prenom
    FROM notes n
    JOIN etudiants e ON n.id_etudiant = e.id
    WHERE n.fichier_path = ?
    ORDER BY e.numero_etudiant
");
$stmt->execute([$fichier['chemin_fichier']]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour déterminer le statut selon la note
function getStatut($note) {
    if ($note >= 10) {
        return ['validée', 'success'];
    } else {
        return ['non validée', 'danger'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voir les Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge {
            min-width: 100px;
            padding: 8px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Notes - <?php echo htmlspecialchars($fichier['code_ue'] . ' - ' . $fichier['nom_ue']); ?></h1>
            <a href="index.php" class="btn btn-secondary">Retour</a>
        </div>
        <h3>Session <?php echo htmlspecialchars(ucfirst($fichier['type_session'])); ?></h3>

        <div class="card mt-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Note</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notes as $note): 
                                $statut = getStatut($note['note']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($note['numero_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($note['nom']); ?></td>
                                <td><?php echo htmlspecialchars($note['prenom']); ?></td>
                                <td><?php echo number_format($note['note'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statut[1]; ?>">
                                        <?php echo ucfirst($statut[0]); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 