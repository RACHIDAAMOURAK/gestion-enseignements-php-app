<?php
// Définir le titre de la page
$page_title = "Consultation des EDT par enseignant";
// Utiliser le header principal
require_once "../../gestion-module-ue/includes/header.php";
require_once "../includes/config.php";

// Récupération de la liste des enseignants
$query_enseignants = "SELECT DISTINCT u.id, u.nom, u.prenom 
                     FROM utilisateurs u 
                     WHERE u.role IN ('enseignant', 'coordinateur', 'vacataire')
                     ORDER BY u.nom, u.prenom";

$result_enseignants = $conn->query($query_enseignants);

// Récupération de la semaine actuelle si non spécifiée
$semaine = isset($_GET['semaine']) ? $_GET['semaine'] : date('Y-m-d', strtotime('monday this week'));

// Calcul des dates de la semaine
$date_debut = new DateTime($semaine);
$dates_semaine = [];
for ($i = 0; $i < 6; $i++) {
    $date_courante = clone $date_debut;
    $date_courante->modify("+$i day");
    $dates_semaine[] = $date_courante;
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
?>

<div class="container mt-4">
    <h2>Consultation de l'emploi du temps par enseignant</h2>
    
    <div class="card shadow mb-4">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="mr-2">Enseignant :</label>
                        <select name="id_enseignant" class="form-control" required>
                            <option value="">Sélectionner un enseignant</option>
                            <?php while ($enseignant = $result_enseignants->fetch_assoc()): ?>
                                <option value="<?php echo $enseignant['id']; ?>" 
                                    <?php echo (isset($_GET['id_enseignant']) && $_GET['id_enseignant'] == $enseignant['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="mr-2">Semaine du :</label>
                        <input type="date" name="semaine" class="form-control" 
                               value="<?php echo $semaine; ?>" 
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Afficher</button>
                        <?php if (isset($_GET['id_enseignant'])): ?>
                            <div class="btn-group">
                                <a href="export_enseignant.php?id_enseignant=<?php echo $_GET['id_enseignant']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                                <a href="export_pdf.php?id_enseignant=<?php echo $_GET['id_enseignant']; ?>" 
                                   class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <?php
            if (isset($_GET['id_enseignant'])) {
                $id_enseignant = $_GET['id_enseignant'];
                
                // Récupération des séances de l'enseignant
                $query_seances = "SELECT DISTINCT s.*, 
                                    ue.code as code_ue, 
                                    ue.intitule as intitule_ue,
                                    CONCAT(g.type, ' ', g.numero) as nom_groupe,
                                    s.salle,
                                    et.id_filiere,
                                    f.nom as nom_filiere,
                                    et.semestre,
                                    et.annee_universitaire,
                                    u.nom as nom_enseignant
                                FROM utilisateurs u
                                JOIN historique_affectations ha ON u.id = ha.id_utilisateur
                                JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
                                JOIN seances s ON ue.id = s.id_unite_enseignement
                                JOIN emplois_temps et ON s.id_emploi_temps = et.id
                                JOIN filieres f ON et.id_filiere = f.id
                                LEFT JOIN groupes g ON s.id_groupe = g.id
                                WHERE u.id = ? 
                                AND et.statut = 'actif'
                                GROUP BY s.id, s.jour, s.heure_debut, s.type
                                ORDER BY s.jour, s.heure_debut";

                $stmt = $conn->prepare($query_seances);
                $stmt->bind_param("i", $id_enseignant);
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
                                                    echo formater_seance_enseignant($seance);
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
                    echo '<div class="alert alert-info">Aucune séance trouvée pour cet enseignant.</div>';
                endif;
            } else {
                echo '<div class="alert alert-info">Veuillez sélectionner un enseignant.</div>';
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

<?php
function formater_seance_enseignant($seance) {
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
    $html .= '<i class="fas fa-graduation-cap"></i> ' . htmlspecialchars($seance['nom_filiere']) . ' - S' . $seance['semestre'] . '<br>';
    $html .= '<i class="fas fa-door-open"></i> ' . htmlspecialchars($seance['salle']);
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

require_once "../../gestion-module-ue/includes/footer.php";
?>