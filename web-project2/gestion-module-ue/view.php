<?php
require_once 'includes/header.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Récupérer les détails de l'UE avec la filière
$query = "SELECT ue.*, d.nom as nom_departement, 
          CONCAT(u.prenom, ' ', u.nom) as nom_responsable,
          f.nom as nom_filiere
          FROM unites_enseignement ue
          LEFT JOIN departements d ON ue.id_departement = d.id
          LEFT JOIN utilisateurs u ON ue.id_responsable = u.id
          LEFT JOIN filieres f ON ue.id_filiere = f.id
          WHERE ue.id = '$id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$ue = mysqli_fetch_assoc($result);

// Fonction pour obtenir la classe Bootstrap selon le statut
function getStatusClass($status) {
    switch ($status) {
        case 'disponible':
            return 'success';
        case 'affecte':
            return 'primary';
        case 'vacant':
            return 'warning';
        default:
            return 'secondary';
    }
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>Détails de l'UE : <?= htmlspecialchars($ue['code']) ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width: 200px;">Code</th>
                            <td><?= htmlspecialchars($ue['code']) ?></td>
                        </tr>
                        <tr>
                            <th>Intitulé</th>
                            <td><?= htmlspecialchars($ue['intitule']) ?></td>
                        </tr>
                        <tr>
                            <th>Département</th>
                            <td><?= htmlspecialchars($ue['nom_departement']) ?></td>
                        </tr>
                        <tr>
                            <th>Spécialité</th>
                            <td><?= htmlspecialchars($ue['specialite']) ?></td>
                        </tr>
                        <tr>
                            <th>Filière</th>
                            <td>
                                <?php if (!empty($ue['nom_filiere'])): ?>
                                    <?= htmlspecialchars($ue['nom_filiere']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Aucune filière associée</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Responsable</th>
                            <td><?= $ue['nom_responsable'] ? htmlspecialchars($ue['nom_responsable']) : 'Non assigné' ?></td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                <span class="badge bg-<?= getStatusClass($ue['statut']) ?>">
                                    <?= ucfirst(htmlspecialchars($ue['statut'])) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Semestre</th>
                            <td><?= htmlspecialchars($ue['semestre']) ?></td>
                        </tr>
                        <tr>
                            <th>Crédits</th>
                            <td><?= htmlspecialchars($ue['credits']) ?></td>
                        </tr>
                        <tr>
                            <th>Volume horaire CM</th>
                            <td><?= htmlspecialchars($ue['volume_horaire_cm']) ?> heures</td>
                        </tr>
                        <tr>
                            <th>Volume horaire TD</th>
                            <td><?= htmlspecialchars($ue['volume_horaire_td']) ?> heures</td>
                        </tr>
                        <tr>
                            <th>Volume horaire TP</th>
                            <td><?= htmlspecialchars($ue['volume_horaire_tp']) ?> heures</td>
                        </tr>
                        <tr>
                            <th>Année universitaire</th>
                            <td><?= htmlspecialchars($ue['annee_universitaire']) ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?= nl2br(htmlspecialchars($ue['description'])) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <a href="edit.php?id=<?= $ue['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
            </a>
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
        }
.table th {
    background-color:#1B2438;
    font-weight: 600;
    color:var(--text-muted);
}
.table td, .table th {
    padding: 1rem;
    vertical-align: middle;
}
.table-bordered {
    border: 1px solid  #2A3854;
}
.table-bordered td, .table-bordered th {
    border: 1px solid #2A3854;
}
</style>

<?php require_once 'includes/footer.php'; ?> 