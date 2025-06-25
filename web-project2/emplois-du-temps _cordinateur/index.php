<?php
// Utiliser le header principal commun à toute l'application
$page_title = "Gestion des emplois du temps";
require_once "../gestion-module-ue/includes/header.php";
require_once "./includes/config.php";

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

// Initialisation des filtres
$id_enseignant = isset($_GET['id_enseignant']) ? intval($_GET['id_enseignant']) : 1;
$semaine = isset($_GET['semaine']) ? $_GET['semaine'] : date('Y-m-d', strtotime('monday this week'));
$annee_univ = isset($_GET['annee_univ']) ? $_GET['annee_univ'] : date('Y');

// Récupération de la liste des enseignants
$query_enseignants = "SELECT id, nom, prenom FROM utilisateurs WHERE role IN ('enseignant', 'coordinateur', 'vacataire') ORDER BY nom, prenom";
$result_enseignants = $conn->query($query_enseignants);

// Calcul des dates de la semaine
$date_debut = new DateTime($semaine);
$dates_semaine = [];
for ($i = 0; $i < 6; $i++) {
    $date_courante = clone $date_debut;
    $date_courante->modify("+$i day");
    $dates_semaine[] = $date_courante;
}

// Récupération des séances
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
                WHERE s.id_emploi_temps = ?";

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = [
    '08:00',
    '10:00',
    '12:00',
    '14:00',
    '16:00',
    '18:00'
];
?>

<div class="container mt-4">
    <h2>Emploi du temps</h2>
    
    <?php
    // Récupérer tous les emplois du temps (filtered by filiere for coordonnateurs)
    $query_all_emplois = "SELECT et.*, f.nom as nom_filiere 
                         FROM emplois_temps et 
                         JOIN filieres f ON et.id_filiere = f.id 
                         WHERE et.statut = 'actif'";

    $params_all_emplois = [];
    $types_all_emplois = "";

    if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
        $query_all_emplois .= " AND et.id_filiere = ?";
        $params_all_emplois[] = $coordonnateur_filiere_id;
        $types_all_emplois .= "i";
    }

    $query_all_emplois .= " ORDER BY et.annee_universitaire DESC, et.semestre";

    $stmt_all_emplois = $conn->prepare($query_all_emplois);
    
    $result_all_emplois = false; // Initialize to false
    
    if ($stmt_all_emplois) {
        if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
             $stmt_all_emplois->bind_param($types_all_emplois, ...$params_all_emplois);
        }
        $stmt_all_emplois->execute();
        $result_all_emplois = $stmt_all_emplois->get_result();
    } else {
        // Handle error if statement preparation failed
        echo '<div class="alert alert-danger">Erreur lors de la préparation de la requête des emplois du temps.</div>';
    }

    ?>

    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Emploi du temps :</label>
                    <select name="id_emploi" class="form-control" onchange="this.form.submit()">
                        <option value="">Sélectionner un emploi du temps</option>
                        <?php if ($result_all_emplois): // Check if query was successful before fetching ?>
                             <?php while ($emploi = $result_all_emplois->fetch_assoc()): ?>
                                <option value="<?php echo $emploi['id']; ?>" <?php echo (isset($_GET['id_emploi']) && $_GET['id_emploi'] == $emploi['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emploi['nom_filiere'] . ' - S' . $emploi['semestre'] . ' - ' . $emploi['annee_universitaire']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <?php
            if (isset($_GET['id_emploi'])) {
                $id_emploi_temps = $_GET['id_emploi'];
                
                $stmt = $conn->prepare($query_seances);
                $stmt->bind_param("i", $id_emploi_temps);
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
                                                    echo formater_seance($seance);
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
            } else {
                echo '<div class="alert alert-info">Veuillez sélectionner un emploi du temps.</div>';
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
    background-color: var(--secondary-bg);
    padding: 12px;
    border: 1px solid #dee2e6;
}

.font-weight-bold {
    font-weight: bold;
}
</style>

<?php
// Fonction pour formater l'affichage d'une séance
function formater_seance($seance) {
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

// Utiliser le footer principal commun à toute l'application
require_once "../gestion-module-ue/includes/footer.php";
?>