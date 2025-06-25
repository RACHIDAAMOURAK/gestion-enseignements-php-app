<?php
// Définir le titre de la page
$page_title = "Gestion des Séances";
// Utiliser le header principal
require_once "../gestion-module-ue/includes/header.php";
require_once "includes/config.php";

// Get the current user's role and filiere if they are a coordonnateur
$current_user_id = $_SESSION['user_id']; // Assuming 'user_id' is the correct session key
$is_coordonnateur = false;
$coordonnateur_filiere_id = null;

$user_query = $conn->prepare("SELECT role, id_filiere FROM utilisateurs WHERE id = ?");
$user_query->bind_param("i", $current_user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_data = $user_result->fetch_assoc()) {
    if ($user_data['role'] === 'coordonnateur') {
        $is_coordonnateur = true;
        $coordonnateur_filiere_id = $user_data['id_filiere'];
    }
}
$user_query->close();

// Récupération de tous les emplois du temps actifs
$query_emplois = "SELECT et.*, f.nom as nom_filiere 
                 FROM emplois_temps et 
                 JOIN filieres f ON et.id_filiere = f.id 
                 WHERE et.statut = 'actif'";

$params_emplois = [];
$types_emplois = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_emplois .= " AND et.id_filiere = ?";
    $params_emplois[] = $coordonnateur_filiere_id;
    $types_emplois .= "i";
}

$query_emplois .= " ORDER BY et.annee_universitaire DESC, et.semestre";

$stmt_emplois = $conn->prepare($query_emplois);

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $stmt_emplois->bind_param($types_emplois, ...$params_emplois);
}

$stmt_emplois->execute();
$result_emplois = $stmt_emplois->get_result();

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des Séances</h2>
        <a href="ajouter_seance.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter une séance
        </a>
    </div>

    <div class="card shadow">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Emploi du temps :</label>
                    <select name="id_emploi" class="form-control" onchange="this.form.submit()">
                        <option value="">Sélectionner un emploi du temps</option>
                        <?php while ($emploi = $result_emplois->fetch_assoc()): ?>
                            <option value="<?php echo $emploi['id']; ?>" <?php echo (isset($_GET['id_emploi']) && $_GET['id_emploi'] == $emploi['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emploi['nom_filiere'] . ' - S' . $emploi['semestre'] . ' - ' . $emploi['annee_universitaire']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <?php
            if (isset($_GET['id_emploi'])) {
                $id_emploi_temps = $_GET['id_emploi'];
                
                // Récupération des séances pour l'emploi du temps sélectionné
                $query_seances = "SELECT s.*, 
                                    ue.code as code_ue, 
                                    ue.intitule as intitule_ue,
                                    CONCAT(g.type, ' ', g.numero) as nom_groupe,
                                    s.salle,
                                    COALESCE(ha.nom_utilisateur, '??') as nom_enseignant
                                FROM seances s
                                LEFT JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
                                LEFT JOIN groupes g ON s.id_groupe = g.id
                                LEFT JOIN historique_affectations ha ON (ue.id = ha.id_unite_enseignement AND s.type = ha.type_cours)
                                WHERE s.id_emploi_temps = ?
                                ORDER BY s.jour, s.heure_debut";
                
                $stmt = $conn->prepare($query_seances);
                $stmt->bind_param("i", $id_emploi_temps);
                $stmt->execute();
                $result_seances = $stmt->get_result();
                
                if ($result_seances->num_rows > 0) {
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
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Horaire</th>
                                    <th>UE</th>
                                    <th>Type</th>
                                    <th>Groupe</th>
                                    <th>Salle</th>
                                    <th>Enseignant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($seance = $result_seances->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seance['jour']); ?></td>
                                        <td><?php echo substr($seance['heure_debut'], 0, 5) . ' - ' . substr($seance['heure_fin'], 0, 5); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($seance['code_ue']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($seance['intitule_ue']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($seance['type']); ?></td>
                                        <td><?php echo $seance['type'] === 'CM' ? '-' : htmlspecialchars($seance['nom_groupe'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($seance['salle'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($seance['nom_enseignant'] ?? '-'); ?></td>
                                        <td>
                                            <a href="modifier_seance.php?id=<?php echo $seance['id']; ?>" class="btn btn-sm btn-warning mr-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" onclick="confirmerSuppression(<?php echo $seance['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                } else {
                    echo '<div class="alert alert-info">Aucune séance n\'a été trouvée pour cet emploi du temps.</div>';
                }
            } else {
                echo '<div class="alert alert-info">Veuillez sélectionner un emploi du temps pour voir les séances.</div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
function confirmerSuppression(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')) {
        window.location.href = 'supprimer_seance.php?id=' + id;
    }
}
</script>

<?php require_once "../gestion-module-ue/includes/footer.php"; ?> 