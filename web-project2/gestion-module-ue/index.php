<?php

require_once 'includes/header.php';

$coordinateur_filiere_id = null;
// Si l'utilisateur est un coordonnateur, récupérer son ID de filière
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'coordonnateur') {
    $user_id = $_SESSION['user_id'];
    $stmt_filiere = mysqli_prepare($conn, "SELECT id_filiere FROM utilisateurs WHERE id = ?");
    mysqli_stmt_bind_param($stmt_filiere, "i", $user_id);
    mysqli_stmt_execute($stmt_filiere);
    $result_filiere = mysqli_stmt_get_result($stmt_filiere);
    if ($row_filiere = mysqli_fetch_assoc($result_filiere)) {
        $coordinateur_filiere_id = $row_filiere['id_filiere'];
    }
    mysqli_stmt_close($stmt_filiere);
}

// Récupérer les listes pour les filtres
$query_departements = "SELECT * FROM departements ORDER BY nom";
$result_departements = mysqli_query($conn, $query_departements);

$query_specialites = "SELECT DISTINCT specialite FROM unites_enseignement WHERE specialite IS NOT NULL ORDER BY specialite"; // Filter out NULL specialties for the dropdown
$result_specialites = mysqli_query($conn, $query_specialites);

// Traitement des filtres
$where_conditions = [];
$params = [];
$param_types = '';

// Appliquer le filtre par filière pour le coordonnateur
if ($coordinateur_filiere_id !== null) {
    $where_conditions[] = "ue.id_filiere = ?";
    $params[] = $coordinateur_filiere_id;
    $param_types .= 'i';
}

if (isset($_GET['departement']) && !empty($_GET['departement'])) {
    $where_conditions[] = "ue.id_departement = ?";
    $params[] = $_GET['departement'];
    $param_types .= 'i';
}

if (isset($_GET['specialite']) && !empty($_GET['specialite'])) {
    $where_conditions[] = "ue.specialite = ?";
    $params[] = $_GET['specialite'];
    $param_types .= 's';
}

if (isset($_GET['semestre']) && !empty($_GET['semestre'])) {
    $where_conditions[] = "ue.semestre = ?";
    $params[] = $_GET['semestre'];
    $param_types .= 'i';
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where_conditions[] = "(ue.code LIKE ? OR ue.intitule LIKE ? OR ue.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'sss';
}

if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $where_conditions[] = "ue.statut = ?";
    $params[] = $_GET['statut'];
    $param_types .= 's';
}

// Construction de la requête
$query = "SELECT DISTINCT ue.*, d.nom as departement_nom, f.nom as filiere_nom 
          FROM unites_enseignement ue 
          LEFT JOIN departements d ON ue.id_departement = d.id 
          LEFT JOIN filieres f ON ue.id_filiere = f.id";

// Si l'utilisateur est un coordonnateur, filtrer par sa filière
if ($coordinateur_filiere_id !== null) {
    $query .= " WHERE ue.id_filiere = ?";
    $params = [$coordinateur_filiere_id];
    $param_types = "i";
} else {
    $params = [];
    $param_types = "";
}

// Ajouter les autres filtres si présents
if (isset($_GET['departement']) && !empty($_GET['departement'])) {
    $query .= ($coordinateur_filiere_id !== null ? " AND" : " WHERE") . " ue.id_departement = ?";
    $params[] = $_GET['departement'];
    $param_types .= "i";
}

if (isset($_GET['specialite']) && !empty($_GET['specialite'])) {
    $query .= ($params ? " AND" : " WHERE") . " ue.specialite = ?";
    $params[] = $_GET['specialite'];
    $param_types .= "s";
}

$query .= " ORDER BY ue.code";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

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
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Liste des Unités d'Enseignement</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle UE
        </a>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="departement" class="form-label">Département</label>
                    <select class="form-select" id="departement" name="departement">
                        <option value="">Tous les départements</option>
                        <?php while ($dept = mysqli_fetch_assoc($result_departements)): ?>
                            <option value="<?= $dept['id'] ?>" <?= (isset($_GET['departement']) && $_GET['departement'] == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['nom']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="specialite" class="form-label">Spécialité</label>
                    <select class="form-select" id="specialite" name="specialite">
                        <option value="">Toutes les spécialités</option>
                        <?php while ($spec = mysqli_fetch_assoc($result_specialites)): ?>
                            <option value="<?= htmlspecialchars($spec['specialite']) ?>" <?= (isset($_GET['specialite']) && $_GET['specialite'] == $spec['specialite']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($spec['specialite']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="semestre" class="form-label">Semestre</label>
                    <select class="form-select" id="semestre" name="semestre">
                        <option value="">Tous les semestres</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['semestre']) && $_GET['semestre'] == $i) ? 'selected' : '' ?>>
                                Semestre <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="disponible" <?= (isset($_GET['statut']) && $_GET['statut'] == 'disponible') ? 'selected' : '' ?>>Disponible</option>
                        <option value="affecte" <?= (isset($_GET['statut']) && $_GET['statut'] == 'affecte') ? 'selected' : '' ?>>Affecté</option>
                        <option value="vacant" <?= (isset($_GET['statut']) && $_GET['statut'] == 'vacant') ? 'selected' : '' ?>>Vacant</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                           placeholder="Code, intitulé ou description...">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table ">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Intitulé</th>
                    <th>Département</th>
                    <th>Spécialité</th>
                    <th>Semestre</th>
                    <th>Crédits</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while($ue = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($ue['code']) ?></td>
                            <td><?= htmlspecialchars($ue['intitule']) ?></td>
                            <td><?= htmlspecialchars($ue['departement_nom']) ?></td>
                            <td><?= htmlspecialchars($ue['specialite']) ?></td>
                            <td><?= htmlspecialchars($ue['semestre']) ?></td>
                            <td><?= htmlspecialchars($ue['credits']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $ue['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="view.php?id=<?= $ue['id'] ?>" class="btn btn-sm btn-info" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $ue['id'] ?>)" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucune unité d'enseignement trouvée</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement cette UE ?')) {
        window.location.href = 'delete.php?id=' + id + '&action=delete';
    }
}
</script>

<?php 
mysqli_stmt_close($stmt);

require_once 'includes/footer.php';

?> 