<?php
require_once 'includes/config.php';

if (!isset($_GET['id_ue']) || !isset($_GET['type_session'])) {
    header('Location: index.php?error=' . urlencode('Paramètres manquants'));
    exit;
}

$id_ue = $_GET['id_ue'];
$type_session = $_GET['type_session'];

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['note_id'])) {
    $note_id = $_POST['note_id'];
    $nouveau_statut = $_POST['action'];
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
    
    if (in_array($nouveau_statut, ['soumise', 'validee', 'rejetee'])) {
        $stmt = $conn->prepare("
            UPDATE notes 
            SET statut = ?, 
                commentaire = ?,
                date_modification = NOW()
            WHERE id = ?");
        $stmt->execute([$nouveau_statut, $commentaire, $note_id]);
    }
}

// Récupération des notes
$stmt = $conn->prepare("
    SELECT DISTINCT 
        n.id,
        n.id_etudiant as numero_etudiant,
        e.nom,
        e.prenom,
        n.note,
        n.date_soumission,
        CASE 
            WHEN n.note >= 10 THEN 'validee'
            WHEN n.note < 10 THEN 'non validee'
        END as statut
    FROM notes n
    JOIN etudiants e ON n.id_etudiant = e.id
    WHERE n.id_unite_enseignement = ?
    AND n.type_session = ?
    ORDER BY e.nom, e.prenom");

$stmt->execute([$id_ue, $type_session]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations de l'UE
$stmt = $conn->prepare("
    SELECT code, intitule 
    FROM unites_enseignement 
    WHERE id = ?");
$stmt->execute([$id_ue]);
$ue = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes des Étudiants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge {
            width: 100px;
            text-align: center;
        }
        .note-actions {
            display: flex;
            gap: 5px;
        }
        .modal-body textarea {
            width: 100%;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Notes des Étudiants</h1>
            <a href="index.php" class="btn btn-secondary">Retour</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']); ?> 
                    (<?php echo ucfirst(htmlspecialchars($type_session)); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Numéro Étudiant</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Note</th>
                                <th>Date de Soumission</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($note['numero_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($note['nom']); ?></td>
                                <td><?php echo htmlspecialchars($note['prenom']); ?></td>
                                <td><?php echo number_format($note['note'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($note['date_soumission'])); ?></td>
                                <td>
                                    <span class="badge status-badge bg-<?php 
                                        echo $note['statut'] === 'validee' ? 'success' : 'danger';
                                    ?>">
                                        <?php echo ucfirst($note['statut']); ?>
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

    <!-- Modal pour le commentaire -->
    <div class="modal fade" id="commentaireModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un commentaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="statutForm" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="note_id" id="note_id">
                            <input type="hidden" name="action" id="action">
                            <div class="mb-3">
                                <label for="commentaire" class="form-label">Commentaire (optionnel)</label>
                                <textarea class="form-control" id="commentaire" name="commentaire"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Confirmer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changerStatut(noteId, statut, commentaireActuel) {
            const modal = new bootstrap.Modal(document.getElementById('commentaireModal'));
            document.getElementById('note_id').value = noteId;
            document.getElementById('action').value = statut;
            document.getElementById('commentaire').value = commentaireActuel;
            modal.show();
        }
    </script>
</body>
</html> 