<?php
// Définir le titre de la page
$page_title = "Affectation des UEs Vacantes";
require_once "../gestion-module-ue/includes/header.php";

// Inclure le fichier CSS spécifique au module vacataire
echo '<link rel="stylesheet" href="style.css">';

// Connexion à la base de données est déjà gérée dans le header

// Récupérer toutes les UEs vacantes
try {
    $stmt = $conn->prepare("
        SELECT 
            uv.id,
            uv.annee_universitaire,
            uv.semestre,
            uv.type_cours,
            uv.volume_horaire,
            uv.date_declaration,
            ue.intitule,
            d.nom as departement
        FROM unites_vacantes_vacataires uv
        JOIN unites_enseignement ue ON uv.id_unite_enseignement = ue.id
        JOIN departements d ON uv.id_departement = d.id
        ORDER BY uv.date_declaration DESC
    ");
    $stmt->execute();
    $ues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Récupérer la liste des vacataires
    $query_vacataires = "SELECT id, nom, prenom, specialite, id_departement
                        FROM utilisateurs 
                        WHERE role = 'vacataire' AND actif = 1 
                        ORDER BY nom, prenom";
    $vacataires = $conn->query($query_vacataires)->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
}
?>
<style>
     :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
            <style>
    .table {
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table thead th {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 1rem;
        }

        .table tbody td {
            border-color: var(--border-color);
            padding: 1rem;
            vertical-align: middle;
        }
    </style>
        
        </style>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary bg-gradient text-white">
                    <h4 class="mb-0">Affectation des UEs vacantes</h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message_type'] ?>" role="alert">
                            <?= $_SESSION['message'] ?>
                        </div>
                        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Département</th>
                                    <th>Intitulé UE</th>
                                    <th>Type de cours</th>
                                    <th>Semestre</th>
                                    <th>Volume horaire</th>
                                    <th>Année universitaire</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ues)): ?>
                                    <?php foreach ($ues as $ue): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ue['departement']) ?></td>
                                            <td><?= htmlspecialchars($ue['intitule']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $ue['type_cours'] === 'CM' ? 'info' : ($ue['type_cours'] === 'TD' ? 'primary' : 'success') ?>">
                                                    <?= htmlspecialchars($ue['type_cours']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($ue['semestre']) ?></td>
                                            <td><?= htmlspecialchars($ue['volume_horaire']) ?> h</td>
                                            <td><?= htmlspecialchars($ue['annee_universitaire']) ?></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#affectationModal"
                                                        data-ue-id="<?= $ue['id'] ?>"
                                                        onclick="setUeId(<?= $ue['id'] ?>)">
                                                    <i class="fas fa-user-plus me-1"></i> Affecter
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune UE vacante disponible.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'affectation -->
<div class="modal fade" id="affectationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-gradient text-white">
                <h5 class="modal-title">Affecter un vacataire</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="traiter_affectation.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_ue" id="id_ue">
                    <div class="mb-3">
                        <label for="id_vacataire" class="form-label">Sélectionner un vacataire :</label>
                        <select class="form-select" name="id_vacataire" id="id_vacataire" required>
                            <option value="">-- Sélectionner un vacataire --</option>
                            <?php
                            try {
                                $stmt = $conn->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'vacataire' AND actif = 1");
                                $stmt->execute();
                                $vacataires = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                foreach ($vacataires as $vacataire) {
                                    echo "<option value='" . $vacataire['id'] . "'>" . 
                                         htmlspecialchars($vacataire['nom'] . " " . $vacataire['prenom']) . 
                                         "</option>";
                                }
                            } catch (Exception $e) {
                                echo "<option value=''>Erreur lors du chargement des vacataires</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire (optionnel) :</label>
                        <textarea class="form-control" name="commentaire" id="commentaire" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Affecter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setUeId(id) {
        document.getElementById('id_ue').value = id;
    }
    
    // Activer les sous-menus
    document.addEventListener('DOMContentLoaded', function() {
        const submenuItems = document.querySelectorAll('.menu-item.has-submenu');
        submenuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target === this || e.target.parentElement === this) {
                    e.preventDefault();
                    this.classList.toggle('open');
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('show');
                    }
                }
            });
        });
        
        // Ouvrir automatiquement le sous-menu actif
        const activeSubItems = document.querySelectorAll('.submenu .menu-item.active');
        activeSubItems.forEach(item => {
            const parent = item.closest('.submenu');
            if (parent) {
                parent.classList.add('show');
                const trigger = parent.previousElementSibling;
                if (trigger && trigger.classList.contains('has-submenu')) {
                    trigger.classList.add('open');
                }
            }
        });
    });
</script>

<?php
// Fermeture du conteneur principal et inclusion du pied de page
echo '</div></div></div>';
require_once "../gestion-module-ue/includes/footer.php";
?> 