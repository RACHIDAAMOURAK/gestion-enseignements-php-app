<?php
// Définir le titre de la page
$page_title = "Ajouter une séance";
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

// Récupération des données nécessaires pour le formulaire
$query_ue = "SELECT id, code, intitule FROM unites_enseignement";
$params_ue = [];
$types_ue = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_ue .= " WHERE id_filiere = ?";
    $params_ue[] = $coordonnateur_filiere_id;
    $types_ue .= "i";
}

$query_ue .= " ORDER BY code";

$stmt_ue = $conn->prepare($query_ue);
if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $stmt_ue->bind_param($types_ue, ...$params_ue);
}
$stmt_ue->execute();
$result_ue = $stmt_ue->get_result();

$query_groupes = "SELECT id, type, numero, id_filiere FROM groupes";
$params_groupes = [];
$types_groupes = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_groupes .= " WHERE id_filiere = ?";
    $params_groupes[] = $coordonnateur_filiere_id;
    $types_groupes .= "i";
}

$query_groupes .= " ORDER BY type, numero";

$stmt_groupes = $conn->prepare($query_groupes);
if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $stmt_groupes->bind_param($types_groupes, ...$params_groupes);
}
$stmt_groupes->execute();
$result_groupes = $stmt_groupes->get_result();

$query_salles = "SELECT DISTINCT salle FROM seances ORDER BY salle";
$result_salles = $conn->query($query_salles);

$query_filieres = "SELECT id, nom FROM filieres ORDER BY nom";
$result_filieres = $conn->query($query_filieres);

$query_emplois = "SELECT e.*, f.nom as nom_filiere 
                 FROM emplois_temps e 
                 JOIN filieres f ON e.id_filiere = f.id 
                 WHERE e.statut = 'actif' ";

$params_emplois = [];
$types_emplois = "";

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $query_emplois .= " AND e.id_filiere = ?";
    $params_emplois[] = $coordonnateur_filiere_id;
    $types_emplois .= "i";
}

$query_emplois .= " ORDER BY e.annee_universitaire DESC, e.semestre";

$stmt_emplois = $conn->prepare($query_emplois);

if ($is_coordonnateur && $coordonnateur_filiere_id !== null) {
    $stmt_emplois->bind_param($types_emplois, ...$params_emplois);
}

$stmt_emplois->execute();
$result_emplois = $stmt_emplois->get_result();

// Variable pour stocker le résultat de l'opération
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ue = $_POST['id_ue'];
    $id_groupe = isset($_POST['id_groupe']) ? $_POST['id_groupe'] : null;
    $type = $_POST['type'];
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $salle = $_POST['salle'];
    $id_emploi_temps = $_POST['id_emploi_temps'];

    if (!isset($error)) {
        // Récupérer l'enseignant associé à l'UE et au type de cours
        $query_enseignant = "SELECT nom_utilisateur FROM historique_affectations 
                            WHERE id_unite_enseignement = ? AND type_cours = ?";
        $stmt = $conn->prepare($query_enseignant);
        $stmt->bind_param("is", $id_ue, $type);
        $stmt->execute();
        $result_enseignant = $stmt->get_result();
        $enseignant = $result_enseignant->fetch_assoc();
        $nom_enseignant = $enseignant ? $enseignant['nom_utilisateur'] : null;

        // Vérification des conflits (salle, groupe et enseignant)
        $query_conflit = "SELECT COUNT(*) as nb FROM seances s
                         LEFT JOIN historique_affectations ha ON (s.id_unite_enseignement = ha.id_unite_enseignement AND s.type = ha.type_cours)
                         WHERE s.jour = ? 
                         AND ((s.heure_debut BETWEEN ? AND ? OR s.heure_fin BETWEEN ? AND ?)
                              OR (? BETWEEN s.heure_debut AND s.heure_fin))
                         AND (s.salle = ? OR " . 
                         ($id_groupe ? "s.id_groupe = " . intval($id_groupe) . " OR " : "") .
                         ($nom_enseignant ? "ha.nom_utilisateur = '" . $conn->real_escape_string($nom_enseignant) . "' OR " : "") .
                         "FALSE)";
        
        $stmt = $conn->prepare($query_conflit);
        $stmt->bind_param("sssssss", $jour, $heure_debut, $heure_fin, $heure_debut, $heure_fin, $heure_debut, $salle);
        $stmt->execute();
        $result_conflit = $stmt->get_result();
        $conflit = $result_conflit->fetch_assoc()['nb'];

        if ($conflit > 0) {
            $error = "Il y a un conflit d'horaire ! Vérifiez que la salle, le groupe et l'enseignant sont disponibles sur ce créneau.";
        } else {
            // Insertion de la séance
            if ($type === 'CM') {
                $query_insert = "INSERT INTO seances (id_unite_enseignement, type, jour, heure_debut, heure_fin, salle, id_emploi_temps) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query_insert);
                $stmt->bind_param("isssssi", $id_ue, $type, $jour, $heure_debut, $heure_fin, $salle, $id_emploi_temps);
            } else {
                $query_insert = "INSERT INTO seances (id_unite_enseignement, id_groupe, type, jour, heure_debut, heure_fin, salle, id_emploi_temps) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query_insert);
                $stmt->bind_param("iisssssi", $id_ue, $id_groupe, $type, $jour, $heure_debut, $heure_fin, $salle, $id_emploi_temps);
            }
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Erreur lors de l'ajout de la séance.";
            }
        }
    }
}
?>

<?php if ($success): ?>
<script>
    // Redirection JavaScript au lieu d'utiliser header()
    window.location.href = 'index.php?success=1';
</script>
<?php endif; ?>

<div class="container mt-4">
    <h2>Ajouter une séance</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="id_emploi_temps" class="form-label">Emploi du temps</label>
            <select class="form-select" id="id_emploi_temps" name="id_emploi_temps" required>
                <option value="">Choisir un emploi du temps</option>
                <?php while ($emploi = $result_emplois->fetch_assoc()): ?>
                    <option value="<?php echo $emploi['id']; ?>">
                        <?php echo htmlspecialchars($emploi['nom_filiere'] . 
                              ' - Semestre ' . $emploi['semestre'] . 
                              ' - ' . $emploi['annee_universitaire']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="id_ue" class="form-label">Unité d'enseignement</label>
                <select class="form-select" id="id_ue" name="id_ue" required>
                    <option value="">Choisir une UE</option>
                    <?php while ($ue = $result_ue->fetch_assoc()): ?>
                        <option value="<?php echo $ue['id']; ?>">
                            <?php echo htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="type" class="form-label">Type de séance</label>
                <select class="form-select" id="type" name="type" required onchange="toggleGroupeField()">
                    <option value="">Choisir un type</option>
                    <option value="CM">CM</option>
                    <option value="TD">TD</option>
                    <option value="TP">TP</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="id_groupe" class="form-label">Groupe</label>
                <select class="form-select" id="id_groupe" name="id_groupe" required disabled>
                    <option value="">Choisir un groupe</option>
                    <?php while ($groupe = $result_groupes->fetch_assoc()): ?>
                        <option value="<?php echo $groupe['id']; ?>" data-type="<?php echo $groupe['type']; ?>">
                            <?php echo htmlspecialchars($groupe['type'] . ' ' . $groupe['numero']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="jour" class="form-label">Jour</label>
                <select class="form-select" id="jour" name="jour" required>
                    <option value="">Choisir un jour</option>
                    <option value="Lundi">Lundi</option>
                    <option value="Mardi">Mardi</option>
                    <option value="Mercredi">Mercredi</option>
                    <option value="Jeudi">Jeudi</option>
                    <option value="Vendredi">Vendredi</option>
                    <option value="Samedi">Samedi</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="heure_debut" class="form-label">Heure de début</label>
                <select class="form-select" id="heure_debut" name="heure_debut" required onchange="updateHeureFinOptions()">
                    <option value="">Choisir une heure</option>
                    <option value="08:00">08:00</option>
                    <option value="10:00">10:00</option>
                    <option value="14:00">14:00</option>
                    <option value="16:00">16:00</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="heure_fin" class="form-label">Heure de fin</label>
                <select class="form-select" id="heure_fin" name="heure_fin" required>
                    <option value="">Choisir une heure</option>
                    <option value="10:00">10:00</option>
                    <option value="12:00">12:00</option>
                    <option value="16:00">16:00</option>
                    <option value="18:00">18:00</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="salle" class="form-label">Salle</label>
                <input type="text" class="form-control" id="salle" name="salle" required
                       list="salles_existantes" placeholder="Entrez une salle">
                <datalist id="salles_existantes">
                    <?php 
                    $result_salles->data_seek(0);
                    while ($salle = $result_salles->fetch_assoc()): 
                    ?>
                        <option value="<?php echo htmlspecialchars($salle['salle']); ?>">
                    <?php endwhile; ?>
                </datalist>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <button type="submit" class="btn btn-primary">Ajouter la séance</button>
                <a href="index.php" class="btn btn-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>

<script>
function toggleGroupeField() {
    const typeSelect = document.getElementById('type');
    const groupeSelect = document.getElementById('id_groupe');
    
    if (typeSelect.value === 'CM') {
        groupeSelect.disabled = true;
        groupeSelect.required = false;
        groupeSelect.value = '';
    } else {
        groupeSelect.disabled = false;
        groupeSelect.required = true;
    }
}

function updateHeureFinOptions() {
    const heureDebutSelect = document.getElementById('heure_debut');
    const heureFinSelect = document.getElementById('heure_fin');
    const heureDebut = heureDebutSelect.value;
    
    // Réinitialiser les options
    heureFinSelect.innerHTML = '<option value="">Choisir une heure</option>';
    
    // Ajouter les options d'heure de fin en fonction de l'heure de début
    if (heureDebut === '08:00') {
        addOption(heureFinSelect, '10:00', '10:00');
    } else if (heureDebut === '10:00') {
        addOption(heureFinSelect, '12:00', '12:00');
    } else if (heureDebut === '14:00') {
        addOption(heureFinSelect, '16:00', '16:00');
    } else if (heureDebut === '16:00') {
        addOption(heureFinSelect, '18:00', '18:00');
    }
}

function addOption(select, value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.text = text;
    select.add(option);
}

// Validation du formulaire
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Initialiser les champs au chargement
document.addEventListener('DOMContentLoaded', function() {
    toggleGroupeField();
    updateHeureFinOptions();
});
</script>

<?php 
// Utiliser le footer principal
require_once "../gestion-module-ue/includes/footer.php"; 
?> 