<?php
// Titre de la page
$page_title = "Gérer les emplois du temps";
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

// Récupération des filières
$query_filieres = "SELECT id, nom FROM filieres";
$params = [];
$types = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_filieres .= " WHERE id = ?";
    $params[] = $coordonnateur_filiere_id;
    $types .= "i";
}

$query_filieres .= " ORDER BY nom";

$stmt_filieres = $conn->prepare($query_filieres);
if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $stmt_filieres->bind_param($types, ...$params);
}
$stmt_filieres->execute();
$result_filieres = $stmt_filieres->get_result();

// Store filieres for JavaScript use in modal
$filieres_for_modal = [];
$result_filieres->data_seek(0); // Reset pointer to beginning
while ($f = $result_filieres->fetch_assoc()) {
    $filieres_for_modal[] = $f;
}
// Reset pointer again for main form loop
$result_filieres->data_seek(0);

// Récupération des emplois du temps existants
$query_emplois = "SELECT e.*, f.nom as nom_filiere,
                 COALESCE(e.fichier_path, '') as fichier_path
                 FROM emplois_temps e
                 JOIN filieres f ON e.id_filiere = f.id";

$params_emplois = [];
$types_emplois = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_emplois .= " WHERE e.id_filiere = ?";
    $params_emplois[] = $coordonnateur_filiere_id;
    $types_emplois .= "i";
}

$query_emplois .= " ORDER BY e.annee_universitaire DESC, e.semestre";

$stmt_emplois = $conn->prepare($query_emplois);

// Check if statement preparation was successful before binding and executing
if ($stmt_emplois) {
    // Only bind parameters if there are parameters to bind (i.e., WHERE clause was added)
    if (!empty($params_emplois)) {
        $stmt_emplois->bind_param($types_emplois, ...$params_emplois);
    }
    $stmt_emplois->execute();
    $result_emplois = $stmt_emplois->get_result();
} else {
    // Handle error if statement preparation failed
    $error = "Erreur lors de la préparation de la requête des emplois du temps : " . $conn->error;
    $result_emplois = false; // Ensure $result_emplois is defined even on error
}

// Gestion des messages
$success = null;
$error = null;

// Vérifier si une opération a été effectuée
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'delete') {
        $success = "L'emploi du temps a été supprimé avec succès.";
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $id_filiere = $_POST['id_filiere'];
    $semestre = $_POST['semestre'];
    $annee_universitaire = $_POST['annee_universitaire'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $fichier_path = $_POST['fichier_path']; 
    $statut = $_POST['statut'];

    // --- Validation côté serveur ---
    if (empty($id_filiere)) {
        $error = "Veuillez sélectionner une filière.";
    } elseif (empty($semestre)) {
        $error = "Veuillez sélectionner un semestre.";
    } elseif (empty($annee_universitaire)) {
        $error = "Veuillez sélectionner une année universitaire.";
    } elseif (empty($date_debut)) {
        $error = "Veuillez saisir la date de début.";
    } elseif (empty($date_fin)) {
        $error = "Veuillez saisir la date de fin.";
    } elseif (empty($statut)) {
        $error = "Veuillez sélectionner un statut.";
    }

    // --- Si aucune erreur de validation, continuer --- 
    if (!isset($error)) {
        // Vérifier si un emploi du temps existe déjà pour cette filière/semestre/année
        $query_check = "SELECT id FROM emplois_temps 
                       WHERE id_filiere = ? AND semestre = ? AND annee_universitaire = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("iis", $id_filiere, $semestre, $annee_universitaire);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Un emploi du temps existe déjà pour cette filière, ce semestre et cette année universitaire.";
        } else {
            // Création de l'emploi du temps
            $query_insert = "INSERT INTO emplois_temps (id_filiere, semestre, annee_universitaire, date_debut, date_fin, fichier_path, date_creation, statut) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $conn->prepare($query_insert);
            $stmt->bind_param("iisssss", 
                $id_filiere, 
                $semestre, 
                $annee_universitaire, 
                $date_debut, 
                $date_fin, 
                $fichier_path,
                $statut
            );

            if ($stmt->execute()) {
                $success = "L'emploi du temps a été créé avec succès.";
                // Recharger la liste des emplois du temps
                
                // Re-execute the prepared statement to fetch the updated list
                if ($stmt_emplois) {
                    // No need to bind again, parameters are already bound if applicable
                    $stmt_emplois->execute();
                    $result_emplois = $stmt_emplois->get_result();
                } else {
                    // Handle the case where the prepared statement failed earlier
                     $error = "Erreur lors de la re-préparation de la requête des emplois du temps.";
                     $result_emplois = false; // Ensure $result_emplois is defined
                }
                
            } else {
                $error = "Erreur lors de la création de l'emploi du temps : " . $stmt->error; // Afficher l'erreur exacte si l'insertion échoue après validation
            }
             $stmt->close(); // Close the insert statement
        }
         $stmt_check->close(); // Close the check statement
    }
     // If there was a validation error ($error is set), the code skips the database operations.
     // The error message will be displayed by the existing alert div at the top.
}
?>

<div class="container mt-4">
    <div class="emplois-section">
        <h2>Gérer les emplois du temps</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-6 emploi-form-col">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Créer un nouvel emploi du temps</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="id_filiere" class="form-label">Filière</label>
                            <select class="form-select" id="id_filiere" name="id_filiere" required <?php echo $is_coordonnateur ? 'disabled' : ''; ?>>
                                <?php if (!$is_coordonnateur): // Only show default option if not coordonnateur ?>
                                    <option value="">Choisir une filière</option>
                                <?php endif; ?>
                                <?php while ($filiere = $result_filieres->fetch_assoc()): ?>
                                    <option value="<?php echo $filiere['id']; ?>" <?php echo $is_coordonnateur && $filiere['id'] == $coordonnateur_filiere_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($is_coordonnateur): // Add hidden input for disabled select ?>
                                <input type="hidden" name="id_filiere" value="<?php echo $coordonnateur_filiere_id; ?>">
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="semestre" class="form-label">Semestre</label>
                            <select class="form-select" id="semestre" name="semestre" required>
                                <option value="">Choisir un semestre</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>">Semestre <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="annee_universitaire" class="form-label">Année universitaire</label>
                            <select class="form-select" id="annee_universitaire" name="annee_universitaire" required>
                                <?php 
                                $annee_courante = date('Y');
                                for ($i = 0; $i < 2; $i++) {
                                    $annee = $annee_courante + $i;
                                    $annee_suivante = $annee + 1;
                                    echo "<option value=\"$annee-$annee_suivante\">$annee-$annee_suivante</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                        </div>

                        <div class="mb-3">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                        </div>

                        <div class="mb-3">
                            <label for="fichier_path" class="form-label">Chemin du fichier</label>
                            <input type="text" class="form-control" id="fichier_path" name="fichier_path" placeholder="Chemin vers le fichier de l'emploi du temps">
                        </div>

                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut" required>
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Créer l'emploi du temps</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 emploi-table-col">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Emplois du temps existants</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table  emplois-table">
                            <thead>
                                <tr>
                                    <th>Filière</th>
                                    <th>Semestre</th>
                                    <th style="min-width: 120px;">Année</th>
                                    <th style="min-width: 250px;">Dates</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($emploi = $result_emplois->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emploi['nom_filiere']); ?></td>
                                        <td>S<?php echo $emploi['semestre']; ?></td>
                                        <td><?php echo htmlspecialchars($emploi['annee_universitaire']); ?></td>
                                        <td>
                                            <?php 
                                            if ($emploi['date_debut'] && $emploi['date_fin']) {
                                                echo date('d/m/Y', strtotime($emploi['date_debut'])) . ' au ' . date('d/m/Y', strtotime($emploi['date_fin']));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $emploi['statut']; ?>">
                                                <?php echo ucfirst($emploi['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="index.php?id_emploi=<?php echo $emploi['id']; ?>" class="btn btn-sm btn-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning" title="Modifier"
                                                    data-emploi='<?php echo json_encode([
                                                        "id" => $emploi['id'],
                                                        "id_filiere" => $emploi['id_filiere'],
                                                        "semestre" => $emploi['semestre'],
                                                        "annee_universitaire" => $emploi['annee_universitaire'],
                                                        "date_debut" => $emploi['date_debut'],
                                                        "date_fin" => $emploi['date_fin'],
                                                        "fichier_path" => $emploi['fichier_path'],
                                                        "statut" => $emploi['statut']
                                                    ]); ?>'
                                                    onclick="modifierEmploi(this)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                                    onclick="confirmerSuppression(<?php echo $emploi['id']; ?>)">
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
    </div>
</div>

<!-- Modal de modification -->
<div class="modal fade" id="modifierEmploiModal" tabindex="-1" aria-labelledby="modifierEmploiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifierEmploiModalLabel">Modifier l'emploi du temps</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formModifierEmploi" method="POST" action="modifier_emploi.php">
                    <input type="hidden" id="edit_id" name="id" value="">
                    <input type="hidden" id="edit_id_filiere_hidden" name="id_filiere" value="">

                    <div class="mb-3">
                        <label for="edit_id_filiere" class="form-label">Filière</label>
                        <select class="form-select" id="edit_id_filiere" name="id_filiere" required>
                            <!-- Options injectées en JS -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_semestre" class="form-label">Semestre</label>
                        <select class="form-select" id="edit_semestre" name="semestre" required>
                            <option value="">Choisir un semestre</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>">Semestre <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_annee_universitaire" class="form-label">Année universitaire</label>
                        <select class="form-select" id="edit_annee_universitaire" name="annee_universitaire" required>
                            <?php 
                            $annee_courante = date('Y');
                            for ($i = 0; $i < 2; $i++) {
                                $annee = $annee_courante + $i;
                                $annee_suivante = $annee + 1;
                                echo "<option value=\"$annee-$annee_suivante\">$annee-$annee_suivante</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="edit_date_debut" name="date_debut" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="edit_date_fin" name="date_fin" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_fichier_path" class="form-label">Chemin du fichier</label>
                        <input type="text" class="form-control" id="edit_fichier_path" name="fichier_path" placeholder="Chemin vers le fichier de l'emploi du temps">
                    </div>

                    <div class="mb-3">
                        <label for="edit_statut" class="form-label">Statut</label>
                        <select class="form-select" id="edit_statut" name="statut" required>
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('formModifierEmploi').submit();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Liste des filières pour le JS
var FILIERES = <?php echo json_encode($filieres_for_modal); ?>;
var IS_COORDONNATEUR = <?php echo json_encode($is_coordonnateur); ?>;
var COORDONNATEUR_FILIERE_ID = <?php echo json_encode($coordonnateur_filiere_id); ?>;

function modifierEmploi(button) {
    const emploi = JSON.parse(button.getAttribute('data-emploi'));

    // Remplir la liste des filières
    let filiereSelect = document.getElementById('edit_id_filiere');
    filiereSelect.innerHTML = ''; // Clear existing options
    filiereSelect.disabled = IS_COORDONNATEUR; // Disable if coordonnateur

    let defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Choisir une filière';
    if (!IS_COORDONNATEUR) {
        filiereSelect.appendChild(defaultOption);
    }

    FILIERES.forEach(f => {
        let opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = f.nom;
        if (IS_COORDONNATEUR && f.id == COORDONNATEUR_FILIERE_ID) {
             opt.selected = true; // Pre-select if coordonnateur
        } else if (!IS_COORDONNATEUR && f.id == emploi.id_filiere) {
             opt.selected = true; // Pre-select based on existing data if not coordonnateur
        }
        filiereSelect.appendChild(opt);
    });

    document.getElementById('edit_id').value = emploi.id;
    document.getElementById('edit_id_filiere_hidden').value = emploi.id_filiere;
    document.getElementById('edit_semestre').value = emploi.semestre;
    document.getElementById('edit_annee_universitaire').value = emploi.annee_universitaire;
    document.getElementById('edit_fichier_path').value = emploi.fichier_path || '';
    document.getElementById('edit_statut').value = emploi.statut;
    document.getElementById('edit_date_debut').value = emploi.date_debut;
    document.getElementById('edit_date_fin').value = emploi.date_fin;

    var modal = new bootstrap.Modal(document.getElementById('modifierEmploiModal'));
    modal.show();
}

// Fonction pour confirmer la suppression
function confirmerSuppression(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet emploi du temps ? Cette action supprimera également toutes les séances associées.')) {
        window.location.href = 'supprimer_emploi.php?id=' + id;
    }
}

// Validation des dates
document.getElementById('date_fin').addEventListener('change', function() {
    var dateDebut = new Date(document.getElementById('date_debut').value);
    var dateFin = new Date(this.value);
    
    if (dateFin < dateDebut) {
        alert('La date de fin doit être postérieure à la date de début.');
        this.value = '';
    }
});

document.getElementById('date_debut').addEventListener('change', function() {
    var dateFinInput = document.getElementById('date_fin');
    if (dateFinInput.value) {
        var dateDebut = new Date(this.value);
        var dateFin = new Date(dateFinInput.value);
        
        if (dateFin < dateDebut) {
            alert('La date de début doit être antérieure à la date de fin.');
            this.value = '';
        }
    }
});
</script>

<style>
.emploi-form-col {
    max-width: 420px;
}
.emploi-table-col {
    flex: 1 1 0%;
    min-width: 0;
}
.action-buttons {
    display: flex;
    flex-direction: row;
    gap: 8px;
    align-items: center;
}

:root {
    --primary-bg: #1B2438;
    --secondary-bg: #1F2B47;
    --accent-color: #31B7D1;
    --text-color: #FFFFFF;
    --text-muted: #7086AB;
    --border-color: #2A3854;
}

#modifierEmploiModal .modal-content {
    background: var(--primary-bg);
    color: var(--text-color);
    border-radius: 16px;
    border: none;
}
#modifierEmploiModal .modal-header {
    background: var(--secondary-bg);
    border-bottom: 1px solid var(--border-color);
    color: var(--text-color);
    border-radius: 16px 16px 0 0;
}
#modifierEmploiModal .modal-title {
    color: var(--accent-color);
    font-weight: bold;
}
#modifierEmploiModal .modal-body label {
    color: var(--text-muted);
}
#modifierEmploiModal .form-control, #modifierEmploiModal .form-select {
    background: var(--secondary-bg);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}
#modifierEmploiModal .form-control:focus, #modifierEmploiModal .form-select:focus {
    background: var(--primary-bg);
    color: var(--text-color);
    border-color: var(--accent-color);
    box-shadow: none;
}
#modifierEmploiModal .modal-footer {
    background: var(--secondary-bg);
    border-top: 1px solid var(--border-color);
    border-radius: 0 0 16px 16px;
}
#modifierEmploiModal .btn-primary {
    background: var(--accent-color);
    border: none;
    color: var(--primary-bg);
    font-weight: bold;
    border-radius: 8px;
}
#modifierEmploiModal .btn-secondary {
    background: var(--border-color);
    border: none;
    color: var(--text-color);
    border-radius: 8px;
}
#modifierEmploiModal .btn-close {
    filter: invert(1);
}
</style>

<?php 
// Utiliser le footer principal
require_once "../gestion-module-ue/includes/footer.php"; 
?>