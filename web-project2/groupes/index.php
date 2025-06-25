<?php
require_once '../gestion-module-ue/includes/header.php';
require_once '../gestion-module-ue/includes/db.php';

// Get the ID of the logged-in user
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$coordinateur_filiere_id = null;

// If the user is a 'coordonnateur', get their filiere ID
if ($user_id && $user_role === 'coordonnateur') {
    $stmt_filiere = mysqli_prepare($conn, "SELECT id_filiere FROM utilisateurs WHERE id = ?");
    mysqli_stmt_bind_param($stmt_filiere, "i", $user_id);
    mysqli_stmt_execute($stmt_filiere);
    $result_filiere = mysqli_stmt_get_result($stmt_filiere);
    if ($row_filiere = mysqli_fetch_assoc($result_filiere)) {
        $coordinateur_filiere_id = $row_filiere['id_filiere'];
    }
    mysqli_stmt_close($stmt_filiere);
}

// Récupérer les filières pour le filtre (seulement si ce n'est PAS un coordonnateur ou si le coordonnateur n'a pas de filière)
$query_filieres = "SELECT DISTINCT f.id, f.nom 
                   FROM filieres f 
                   JOIN groupes g ON f.id = g.id_filiere ";

$filter_filiere_params = [];
$filter_filiere_types = '';

if ($coordinateur_filiere_id !== null) {
     $query_filieres .= " WHERE f.id = ?";
     $filter_filiere_params[] = $coordinateur_filiere_id;
     $filter_filiere_types .= 'i';
}

$query_filieres .= " ORDER BY f.nom";

$stmt_filieres = mysqli_prepare($conn, $query_filieres);
if (!empty($filter_filiere_params)) {
    mysqli_stmt_bind_param($stmt_filieres, $filter_filiere_types, ...$filter_filiere_params);
}
mysqli_stmt_execute($stmt_filieres);
$result_filieres = mysqli_stmt_get_result($stmt_filieres);

// Récupérer les paramètres de filtrage depuis GET
$filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$semestre = isset($_GET['semestre']) ? $_GET['semestre'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Initialize conditions for the main query
$where_conditions = [];
$params = [];
$param_types = '';

// Always filter by the coordinator's filiere if applicable
if ($coordinateur_filiere_id !== null) {
    $where_conditions[] = "g.id_filiere = ?";
    $params[] = $coordinateur_filiere_id;
    $param_types .= 'i';
    // If coordinator, pre-select and disable the filiere filter dropdown
    $filiere = $coordinateur_filiere_id; 
}

// Add other filters only if they are present in GET and user is not a coordinator (or if coordinator_filiere_id is null)
// If coordinator, the filiere filter from GET is ignored in favor of the session filiere

if ($semestre) {
    $where_conditions[] = "g.semestre = ?";
    $params[] = $semestre;
    $param_types .= 'i';
}

if ($search) {
    $where_conditions[] = "(u.code LIKE ? OR u.intitule LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $param_types .= 'ss';
}

if ($type) {
    $where_conditions[] = "g.type = ?";
    $params[] = $type;
    $param_types .= 's';
}

// Construction de la requête principale pour les groupes
$query = "SELECT g.*, u.code, u.intitule, f.nom as nom_filiere 
          FROM groupes g 
          JOIN unites_enseignement u ON g.id_unite_enseignement = u.id 
          JOIN filieres f ON g.id_filiere = f.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY g.annee_universitaire DESC, g.semestre, g.type, g.numero";

// Préparation et exécution de la requête principale
$stmt = mysqli_prepare($conn, $query);
if ($stmt && !empty($params)) {
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
        <h2>Gestion des Groupes TD/TP</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Créer un nouveau groupe
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="filiere" class="form-label">Filière</label>
                    <select name="filiere" id="filiere" class="form-select" <?= $coordinateur_filiere_id !== null ? 'disabled' : '' ?>>
                        <option value="">Toutes les filières</option>
                        <?php mysqli_data_seek($result_filieres, 0); // Reset pointer for the select box ?>
                        <?php while ($row = mysqli_fetch_assoc($result_filieres)): ?>
                            <option value="<?php echo $row['id']; ?>" <?php echo ($coordinateur_filiere_id !== null && $filiere == $row['id']) || ($coordinateur_filiere_id === null && $filiere == $row['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['nom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="semestre" class="form-label">Semestre</label>
                    <select name="semestre" id="semestre" class="form-select">
                        <option value="">Tous les semestres</option>
                        <?php for($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $semestre == $i ? 'selected' : ''; ?>>
                                S<?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="TD" <?php echo $type == 'TD' ? 'selected' : ''; ?>>TD</option>
                        <option value="TP" <?php echo $type == 'TP' ? 'selected' : ''; ?>>TP</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Code ou nom de l'UE">
                </div>

                <div class="col-md-2 d-flex align-items-end">
    <button type="submit" class="btn btn-primary me-2">
        <i class="fas fa-filter me-1"></i>
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrows-rotate me-1"></i> 
    </a>
</div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table ">
                    <thead>
                        <tr>
                            <th>UE</th>
                            <th>Filière</th>
                            <th>Type</th>
                            <th>Numéro</th>
                            <th>Effectif</th>
                            <th>Année</th>
                            <th>Semestre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['intitule']); ?></td>
                                <td><?php echo htmlspecialchars($row['nom_filiere']); ?></td>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo htmlspecialchars($row['numero']); ?></td>
                                <td><?php echo htmlspecialchars($row['effectif']); ?></td>
                                <td><?php echo htmlspecialchars($row['annee_universitaire']); ?></td>
                                <td><?php echo htmlspecialchars($row['semestre']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?')) {
        window.location.href = 'delete.php?id=' + id;
    }
}
</script>

<?php require_once '../gestion-module-ue/includes/footer.php'; ?> 