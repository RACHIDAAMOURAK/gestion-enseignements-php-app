<?php
require_once 'includes/config.php';

// Démarrer une session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Inclusion du header
require_once '../header_enseignant.php';
?>

<!-- Main Container (continue depuis header_enseignant.php) -->
<div class="content">
    <div class="container-fluid">
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
                        <button type="submit" class="btn btn-action"><i class="fas fa-upload"></i>  Uploader
                        </button>
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
                    <table class="table ">
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
                                       class="btn btn-action"><i class="fas fa-eye"></i>
                                       </a>
                                    <button type="button" class="btn btn-action" 
                                            onclick="confirmerSuppression(<?php echo $fichier['id']; ?>, '<?php echo htmlspecialchars($fichier['nom_fichier']); ?>')">
                                            <i class="fas fa-trash"></i>
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
                        <button type="button" class="btn btn-action" data-bs-dismiss="modal"><i class="fas fa-chevron-left"></i></button>
                        <button type="submit" class="btn btn-action"> <i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<style>
:root {
    --primary-bg: #1B2438;
    --secondary-bg: #1F2B47;
    --accent-color: #31B7D1;
    --text-color: #FFFFFF;
    --text-muted: #7086AB;
    --border-color: #2A3854;
    --card-hover-transform: translateY(-3px);
    --card-border-radius: 0.75rem;
}
body {
    background-color: var(--primary-bg);
    color: var(--text-color);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
.content {
    padding: 1.5rem;
}
.card {
    background-color: var(--secondary-bg);
    border-radius: var(--card-border-radius);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    margin-bottom: 1.75rem;
    overflow: hidden;
}
.card:hover {
    transform: var(--card-hover-transform);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.card-header {
    background-color: rgba(49, 183, 209, 0.05);
    border-bottom: 1px solid var(--border-color);
    color: var(--accent-color);
    font-weight: 600;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
}
.card-title {
    color: var(--accent-color);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0;
}
.card-body {
    padding: 1.5rem;
}
.table {
    color: var(--text-color);
    margin-bottom: 0;
}
.table thead th {
    background-color: var(--primary-bg);
    color: var(--text-muted);
    font-weight: 500;
    border-bottom: 1px solid var(--border-color);
    padding: 1rem;
}
.table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s ease;
}
.table tbody tr:hover {
    background-color: rgba(49, 183, 209, 0.05);
}
.table td {
    vertical-align: middle;
    padding: 1rem;
}
.badge {
    padding: 0.35em 0.65em;
    border-radius: 0.375rem;
    font-size: 0.875em;
    font-weight: 500;
}
.badge.bg-success { background-color: rgba(52, 199, 89, 0.2) !important; color: #34C759 !important; }
.badge.bg-warning { background-color: rgba(255, 159, 10, 0.2) !important; color: #FF9F0A !important; }
.btn-action, .btn.btn-action {
    background-color: var(--accent-color);
    color: var(--text-color);
    border: none;
    padding: 0.5rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
    margin-right: 0.25rem;
    box-shadow: 0 2px 6px rgba(49, 183, 209, 0.1);
}
.btn-action:hover, .btn.btn-action:hover {
    background-color: #259bb2;
    color: #fff;
    transform: translateY(-2px);
}
.btn-close {
    filter: invert(1);
}
.alert {
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.75rem;
    display: flex;
    align-items: flex-start;
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.alert-success {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}
.alert-danger {
    background-color: rgba(239, 83, 80, 0.1);
    color: #EF5350;
}
.modal-content {
    background: var(--primary-bg);
    color: var(--text-color);
    border: 1.5px solid var(--border-color);
    border-radius: 16px;
}
.modal-header{
    background: var(--secondary-bg);
    border-bottom: 1px solid var(--border-color);
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}
.modal-title, .modal-body,  .modal-header {
    color: var(--text-color);
}
.form-label {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 0.65rem;
    font-weight: 500;
}
.form-control {
    background-color: rgba(27, 36, 56, 0.5);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    padding: 0.85rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.25s ease;
}
.form-control:focus {
    background-color: rgba(27, 36, 56, 0.7);
    border-color: var(--accent-color);
    color: var(--text-color);
    box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
}
.form-control:disabled {
    opacity: 0.8;
    cursor: not-allowed;
    background-color: rgba(27, 36, 56, 0.3);
}
@media (max-width: 992px) {
    .content {
        padding: 1.25rem;
    }
    .card-body {
        padding: 1.25rem;
    }
}
@media (max-width: 768px) {
    .content {
        padding: 1rem;
    }
    .card-body {
        padding: 1rem;
    }
}
</style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmerSuppression(id, nomFichier) {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            document.getElementById('idFichier').value = id;
            document.getElementById('nomFichier').textContent = nomFichier;
            modal.show();
        }
    </script>
<?php
// Inclusion du footer
require_once '../footer.php';
?> 