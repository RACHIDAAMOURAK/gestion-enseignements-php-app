<?php
require_once "../../gestion-module-ue/includes/header.php";
require_once "../includes/config.php";

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

// Récupération de la liste des filières, semestres et années disponibles avec emplois du temps actifs
$query_filieres = "SELECT DISTINCT f.id, f.nom, et.semestre, et.annee_universitaire 
                   FROM filieres f
                   JOIN emplois_temps et ON f.id = et.id_filiere
                   WHERE et.statut = 'actif'";

$params_filieres = [];
$types_filieres = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_filieres .= " AND f.id = ?";
    $params_filieres[] = $coordonnateur_filiere_id;
    $types_filieres .= "i";
}

$query_filieres .= " ORDER BY f.nom, et.semestre, et.annee_universitaire DESC";

$stmt_filieres = $conn->prepare($query_filieres);

if ($stmt_filieres) {
    if (!empty($params_filieres)) {
        $stmt_filieres->bind_param($types_filieres, ...$params_filieres);
    }
    $stmt_filieres->execute();
    $result_filieres = $stmt_filieres->get_result();
} else {
     // Handle error if statement preparation failed
    $error = "Erreur lors de la préparation de la requête des filières disponibles : " . $conn->error;
    $result_filieres = false; // Ensure $result_filieres is defined
}

// Organiser les filières par semestre et année
$filieres_data = [];
// Check if $result_filieres is a valid result set before fetching
if ($result_filieres) {
    while ($row = $result_filieres->fetch_assoc()) {
        if (!isset($filieres_data[$row['id']])) {
            $filieres_data[$row['id']] = [
                'nom' => $row['nom'],
                'semestres' => []
            ];
        }
        if (!isset($filieres_data[$row['id']]['semestres'][$row['semestre']])) {
            $filieres_data[$row['id']]['semestres'][$row['semestre']] = [];
        }
        $filieres_data[$row['id']]['semestres'][$row['semestre']][] = $row['annee_universitaire'];
    }
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
?>

<div class="container mt-4">
    <h2>Consultation de l'emploi du temps par filière</h2>
    
    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <div class="row align-items-center">
                    <div class="col-md-4" <?php echo $is_coordonnateur ? 'style="display: none;"' : ''; ?>>
                        <label class="mr-2">Filière :</label>
                        <select name="id_filiere" class="form-control" <?php echo $is_coordonnateur ? '' : 'required'; ?> onchange="updateSemestres(this.value)" id="filiere_select">
                            <option value="">Sélectionner une filière</option>
                            <?php if (!$is_coordonnateur): // Only show filiere options if not coordonnateur ?>
                                <?php foreach ($filieres_data as $id => $filiere): ?>
                                    <option value="<?php echo $id; ?>" 
                                        <?php echo (isset($_GET['id_filiere']) && $_GET['id_filiere'] == $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if ($is_coordonnateur && $coordonnateur_filiere_id !== null): // Add hidden input for coordonnateur's filiere ?>
                             <input type="hidden" name="id_filiere" value="<?php echo $coordonnateur_filiere_id; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="mr-2">Semestre :</label>
                        <select name="semestre" class="form-control" required id="semestre_select" onchange="updateAnnees()">
                            <option value="">Sélectionner un semestre</option>
                            <?php 
                            $current_filiere_id = $is_coordonnateur ? $coordonnateur_filiere_id : (isset($_GET['id_filiere']) ? $_GET['id_filiere'] : null);
                            if ($current_filiere_id !== null && isset($filieres_data[$current_filiere_id])) {
                                foreach ($filieres_data[$current_filiere_id]['semestres'] as $semestre => $annees) {
                                    echo '<option value="' . $semestre . '" ' . 
                                         (isset($_GET['semestre']) && $_GET['semestre'] == $semestre ? 'selected' : '') . 
                                         '>S' . $semestre . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="mr-2">Année :</label>
                        <select name="annee" class="form-control" required id="annee_select">
                            <option value="">Sélectionner une année</option>
                            <?php 
                             $current_filiere_id = $is_coordonnateur ? $coordonnateur_filiere_id : (isset($_GET['id_filiere']) ? $_GET['id_filiere'] : null);
                            if ($current_filiere_id !== null && isset($_GET['semestre']) && 
                                isset($filieres_data[$current_filiere_id]['semestres'][$_GET['semestre']])) {
                                foreach ($filieres_data[$current_filiere_id]['semestres'][$_GET['semestre']] as $annee) {
                                    echo '<option value="' . $annee . '" ' . 
                                         (isset($_GET['annee']) && $_GET['annee'] == $annee ? 'selected' : '') . 
                                         '>' . $annee . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Afficher</button>
                        <?php 
                         $current_filiere_id = $is_coordonnateur ? $coordonnateur_filiere_id : (isset($_GET['id_filiere']) ? $_GET['id_filiere'] : null);
                        if ($current_filiere_id !== null && isset($_GET['semestre']) && isset($_GET['annee'])): ?>
                            <a href="export.php?type=filiere&id_filiere=<?php echo $current_filiere_id; ?>&semestre=<?php echo $_GET['semestre']; ?>&annee=<?php echo $_GET['annee']; ?>" 
                               class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="export_pdf_filiere.php?id_filiere=<?php echo $current_filiere_id; ?>&semestre=<?php echo $_GET['semestre']; ?>&annee=<?php echo $_GET['annee']; ?>" 
                               class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <?php
            // Determine the filiere ID to use
            $filiere_to_query = null;
            if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
                $filiere_to_query = $coordonnateur_filiere_id;
            } elseif (isset($_GET['id_filiere'])) {
                $filiere_to_query = $_GET['id_filiere'];
            }

            if ($filiere_to_query !== null && isset($_GET['semestre']) && isset($_GET['annee'])) {
                $id_filiere = $filiere_to_query;
                $semestre = $_GET['semestre'];
                $annee = $_GET['annee'];
                
                // Récupérer l'ID de l'emploi du temps
                $query_emploi = "SELECT id FROM emplois_temps 
                                WHERE id_filiere = ? AND semestre = ? AND annee_universitaire = ? AND statut = 'actif'";
                $stmt = $conn->prepare($query_emploi);
                $stmt->bind_param("iis", $id_filiere, $semestre, $annee);
                $stmt->execute();
                $result_emploi = $stmt->get_result();
                
                if ($emploi = $result_emploi->fetch_assoc()) {
                    $id_emploi = $emploi['id'];
                    
                    // Récupération des séances
                    $query_seances = "SELECT s.*, 
                                        ue.code as code_ue, 
                                        ue.intitule as intitule_ue,
                                        CONCAT(g.type, ' ', g.numero) as nom_groupe,
                                        s.salle,
                                        GROUP_CONCAT(DISTINCT COALESCE(ha.nom_utilisateur, '??') SEPARATOR ', ') as nom_enseignant
                                    FROM seances s
                                    LEFT JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
                                    LEFT JOIN groupes g ON s.id_groupe = g.id
                                    LEFT JOIN historique_affectations ha ON (ue.id = ha.id_unite_enseignement AND s.type = ha.type_cours)
                                    WHERE s.id_emploi_temps = ?
                                    GROUP BY s.id
                                    ORDER BY s.jour, s.heure_debut";
                    
                    $stmt = $conn->prepare($query_seances);
                    $stmt->bind_param("i", $id_emploi);
                    $stmt->execute();
                    $result_seances = $stmt->get_result();
                    
                    // Organiser les séances
                    $seances_organisees = [];
                    while ($seance = $result_seances->fetch_assoc()) {
                        $jour = $seance['jour'];
                        $heure_debut = substr($seance['heure_debut'], 0, 5);
                        if (!isset($seances_organisees[$jour])) {
                            $seances_organisees[$jour] = [];
                        }
                        if (!isset($seances_organisees[$jour][$heure_debut])) {
                            $seances_organisees[$jour][$heure_debut] = [];
                        }
                        $seances_organisees[$jour][$heure_debut][] = $seance;
                    }
                    
                    if ($result_seances->num_rows > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 100px;">Horaire</th>
                                    <?php foreach ($jours as $jour): ?>
                                        <th><?php echo $jour; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                for ($i = 0; $i < count($heures) - 1; $i++): 
                                    $heure_debut = $heures[$i];
                                    $heure_fin = $heures[$i + 1];
                                    
                                    // Sauter l'intervalle 12:00-14:00
                                    if ($heure_debut === '12:00' && $heure_fin === '14:00') {
                                        continue;
                                    }
                                ?>
                                    <tr>
                                        <td class="font-weight-bold">
                                            <?php echo $heure_debut . ' - ' . $heure_fin; ?>
                                        </td>
                                        <?php foreach ($jours as $jour): ?>
                                            <td>
                                                <?php
                                                if (isset($seances_organisees[$jour][$heure_debut])) {
                                                    foreach ($seances_organisees[$jour][$heure_debut] as $seance) {
                                                        echo formater_seance_filiere($seance);
                                                    }
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php 
                    else:
                        echo '<div class="alert alert-info">Aucune séance trouvée pour cet emploi du temps.</div>';
                    endif;
                } else {
                    echo '<div class="alert alert-warning">Aucun emploi du temps trouvé pour ces critères.</div>';
                }
            } else {
                echo '<div class="alert alert-info">Veuillez sélectionner une filière, un semestre et une année universitaire.</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.seance {
    padding: 8px;
    margin: 2px;
    border-radius: 6px;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.seance:hover {
    transform: scale(1.02);
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
}

.seance-header {
    margin-bottom: 5px;
    font-weight: bold;
    color: #222;
}

.seance-details {
    font-size: 0.8rem;
    color: #555;
}

.seance-details i {
    width: 16px;
    margin-right: 4px;
}

.cm { background-color: #e3f2fd; border: 1px solid #90caf9; }
.td { background-color: #f3e5f5; border: 1px solid #ce93d8; }
.tp { background-color: #e8f5e9; border: 1px solid #a5d6a7; }

.table td {
    min-width: 180px;
    height: 100px;
    vertical-align: top;
    padding: 8px;
    border: 1px solid #dee2e6;
}

.table th {
    text-align: center;
    background-color:rgb(3, 30, 56);
    padding: 12px;
    border: 1px solid #dee2e6;
}
</style>

<script>
// Données des filières
const filieresData = <?php echo json_encode($filieres_data); ?>;
const IS_COORDONNATEUR_JS = <?php echo json_encode($is_coordonnateur); ?>;
const COORDONNATEUR_FILIERE_ID_JS = <?php echo json_encode($coordonnateur_filiere_id); ?>;

function updateSemestres(filiereIdToUse) {
    const semestreSelect = document.getElementById('semestre_select');
    const anneeSelect = document.getElementById('annee_select');
    
    // Réinitialiser les sélections
    semestreSelect.innerHTML = '<option value="">Sélectionner un semestre</option>';
    anneeSelect.innerHTML = '<option value="">Sélectionner une année</option>';
    
    if (filiereIdToUse && filieresData[filiereIdToUse]) {
        const semestres = filieresData[filiereIdToUse].semestres;
        // Sort semesters numerically
        const sortedSemestres = Object.keys(semestres).sort((a, b) => parseInt(a) - parseInt(b));

        sortedSemestres.forEach(semestre => {
            const option = document.createElement('option');
            option.value = semestre;
            option.textContent = 'S' + semestre;
            semestreSelect.appendChild(option);
        });

        // If a semester was already selected (e.g., on page load from GET params), re-select it
        const selectedSemestre = "<?php echo isset($_GET['semestre']) ? $_GET['semestre'] : ''; ?>";
        if (selectedSemestre) {
            semestreSelect.value = selectedSemestre;
            // The change event listener will call updateAnnees after this function finishes
        }
    }
}

function updateAnnees() {
    // Determine the filiere ID to use
    let filiereIdToUse = null;
    if (IS_COORDONNATEUR_JS) {
        filiereIdToUse = COORDONNATEUR_FILIERE_ID_JS;
    } else {
        const filiereSelect = document.querySelector('select[name="id_filiere"]');
        if (filiereSelect) { 
             filiereIdToUse = filiereSelect.value;
        }
    }

    const semestre = document.getElementById('semestre_select').value;
    const anneeSelect = document.getElementById('annee_select');
    
    // Réinitialiser la sélection
    anneeSelect.innerHTML = '<option value="">Sélectionner une année</option>';
    
    // Check if the required data exists before accessing it
    if (filiereIdToUse !== null && semestre && filieresData[filiereIdToUse] && filieresData[filiereIdToUse].semestres && filieresData[filiereIdToUse].semestres[semestre] && Array.isArray(filieresData[filiereIdToUse].semestres[semestre])) {
        const annees = filieresData[filiereIdToUse].semestres[semestre];
         // Sort years in descending order
        const sortedAnnees = annees.sort().reverse();

        sortedAnnees.forEach(annee => {
            const option = document.createElement('option');
            option.value = annee;
            option.textContent = annee;
            anneeSelect.appendChild(option);
        });

         // If a year was already selected (e.g., on page load from GET params), re-select it
         const selectedAnnee = "<?php echo isset($_GET['annee']) ? $_GET['annee'] : ''; ?>";
         if (selectedAnnee) {
             anneeSelect.value = selectedAnnee;
         }
    }
}

// Add event listener to the semestre select to call updateAnnees when it changes
document.getElementById('semestre_select').addEventListener('change', updateAnnees);

// Initial call to populate semesters and years on page load
window.onload = function() {
    let initialFiliereId = null;
    if (IS_COORDONNATEUR_JS) {
        initialFiliereId = COORDONNATEUR_FILIERE_ID_JS;
    } else {
        const filiereSelect = document.getElementById('filiere_select');
        if (filiereSelect) {
            initialFiliereId = filiereSelect.value; // Get the default or pre-selected value
        }
    }

    if (initialFiliereId !== null) {
         // Populate semesters based on the initial filiere
         updateSemestres(initialFiliereId);

         // If a semester is already selected on load, manually trigger updateAnnees
         // Use a small timeout to ensure updateSemestres has finished updating the semester dropdown
         const selectedSemestre = "<?php echo isset($_GET['semestre']) ? $_GET['semestre'] : ''; ?>";
         if (selectedSemestre) {
             // Wait for semesters to be populated before calling updateAnnees
             // A slightly longer timeout for potentially slower environments
             setTimeout(() => {
                 const semestreSelect = document.getElementById('semestre_select');
                 if (semestreSelect && document.querySelector('#semestre_select option[value="' + selectedSemestre + '"]')) { // Check if element and option exist
                    semestreSelect.value = selectedSemestre; // Ensure selected semester is set
                    updateAnnees();
                 } else if (semestreSelect) {
                      // If the pre-selected semester is not a valid option, still attempt to update years 
                      // with the default selected semester (likely the first one)
                      updateAnnees();
                 }
             }, 100); // Keep a small timeout
         } else if (document.getElementById('semestre_select').value) {
             // If no semester was pre-selected but the semester dropdown has a default value,
             // trigger updateAnnees immediately after semesters are populated.
             updateAnnees();
         }
    }
};

</script>

<?php
function formater_seance_filiere($seance) {
    $type_class = strtolower($seance['type']);
    $html = '<div class="seance ' . $type_class . '">';
    $html .= '<div class="seance-header">';
    $html .= '<strong>' . htmlspecialchars($seance['intitule_ue']) . '</strong><br>';
    if ($seance['type'] !== 'CM') {
        $html .= ' - ' . htmlspecialchars($seance['nom_groupe']);
    }
    $html .= ' (' . htmlspecialchars($seance['type']) . ')';
    $html .= '</div>';
    
    $html .= '<div class="seance-details">';
    $html .= '<i class="fas fa-chalkboard-teacher"></i> Pr. ' . htmlspecialchars($seance['nom_enseignant']) . '<br>';
    $html .= '<i class="fas fa-door-open"></i> ' . htmlspecialchars($seance['salle']);
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

require_once "../../gestion-module-ue/includes/footer.php";
?>