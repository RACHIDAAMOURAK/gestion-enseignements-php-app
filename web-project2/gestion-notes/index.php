<?php
require_once 'includes/config.php';

// Récupération des fichiers uploadés
$stmt = $conn->query("SELECT fn.*, 
                      ue.code as code_ue, 
                      ue.intitule as intitule_ue,
                      u.nom as nom_enseignant,
                      u.prenom as prenom_enseignant,
                      COUNT(n.id) as nombre_notes
                      FROM fichiers_notes fn 
                      JOIN unites_enseignement ue ON fn.id_unite_enseignement = ue.id 
                      JOIN utilisateurs u ON fn.id_enseignant = u.id
                      LEFT JOIN notes n ON fn.id_unite_enseignement = n.id_unite_enseignement 
                          AND fn.type_session = n.type_session
                          AND fn.chemin_fichier = n.fichier_path
                      GROUP BY fn.id
                      ORDER BY fn.date_upload DESC");
$fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de succès ou d'erreur
$message = '';
$message_type = '';
if (isset($_GET['success'])) {
    $message = "Les notes ont été uploadées avec succès. " . 
               (isset($_GET['notes']) ? $_GET['notes'] . " notes ont été traitées." : "");
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $message = "Erreur : " . htmlspecialchars($_GET['error']);
    $message_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Gestion des Notes</h1>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Formulaire d'upload -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Upload des Notes</h5>
            </div>
            <div class="card-body">
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="ue" class="form-label">Unité d'Enseignement</label>
                        <select class="form-select" id="ue" name="id_ue" required>
                            <option value="">Sélectionnez une UE</option>
                            <?php
                            $stmt = $conn->query("
                                SELECT DISTINCT ue.id, ue.code, ue.intitule 
                                FROM unites_enseignement ue
                                JOIN historique_affectations ha ON ue.id = ha.id_unite_enseignement
                                WHERE ha.type_cours = 'CM'
                                ORDER BY ue.code
                            ");
                            while ($ue = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$ue['id']}'>{$ue['code']} - {$ue['intitule']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type_session" class="form-label">Session</label>
                        <select class="form-select" id="type_session" name="type_session" required>
                            <option value="normale">Session Normale</option>
                            <option value="rattrapage">Session de Rattrapage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fichier" class="form-label">Fichier de Notes</label>
                        <input type="file" class="form-control" id="fichier" name="fichier" required accept=".xlsx,.xls,.csv">
                        <div class="form-text">Formats acceptés : XLSX, XLS, CSV</div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Uploader</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des fichiers uploadés -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Fichiers de Notes Uploadés</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>UE</th>
                                <th>Session</th>
                                <th>Enseignant</th>
                                <th>Date d'Upload</th>
                                <th>Statut</th>
                                <th>Nombre de Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichiers as $fichier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fichier['code_ue'] . ' - ' . $fichier['intitule_ue']); ?></td>
                                <td><?php echo ucfirst($fichier['type_session']); ?></td>
                                <td><?php echo htmlspecialchars($fichier['prenom_enseignant'] . ' ' . $fichier['nom_enseignant']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($fichier['date_upload'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $fichier['statut'] === 'traite' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($fichier['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo $fichier['nombre_notes']; ?></td>
                                <td>
                                    <a href="voir_notes.php?fichier=<?php echo $fichier['id']; ?>" 
                                       class="btn btn-sm btn-primary">Consulter</a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="confirmerSuppression(<?php echo $fichier['id']; ?>, '<?php echo htmlspecialchars($fichier['nom_fichier']); ?>')">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le fichier <strong id="nomFichier"></strong> ?</p>
                    <p class="text-danger">Attention : Cette action supprimera également toutes les notes associées à ce fichier.</p>
                </div>
                <div class="modal-footer">
                    <form action="supprimer_fichier.php" method="POST">
                        <input type="hidden" name="id_fichier" id="idFichier">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmerSuppression(id, nomFichier) {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            document.getElementById('idFichier').value = id;
            document.getElementById('nomFichier').textContent = nomFichier;
            modal.show();
        }
    </script>
</body>
</html> 