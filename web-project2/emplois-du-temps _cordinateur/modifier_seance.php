<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/config.php";

// Vérifier si un ID est fourni
if (!isset($_GET['id'])) {
    header('Location: gerer_seances.php');
    exit();
}

$id_seance = intval($_GET['id']);

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $id_ue = $_POST['id_ue'];
    $type = $_POST['type'];
    $salle = $_POST['salle'];
    
    // Correction principale: Gestion correcte de id_groupe
    if ($type === 'CM') {
        // Requête SQL modifiée pour le type CM (sans id_groupe)
        $query = "UPDATE seances SET 
                  jour = ?, 
                  heure_debut = ?, 
                  heure_fin = ?,
                  id_unite_enseignement = ?,
                  type = ?,
                  id_groupe = NULL,
                  salle = ?
                  WHERE id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssissi", $jour, $heure_debut, $heure_fin, $id_ue, $type, $salle, $id_seance);
    } else {
        // Pour TD et TP, vérification que id_groupe est bien fourni
        if (!isset($_POST['id_groupe']) || empty($_POST['id_groupe'])) {
            $message = "Un groupe doit être sélectionné pour les séances de type TD ou TP";
            $message_type = "danger";
        } else {
            $id_groupe = $_POST['id_groupe'];
            
            // Requête SQL avec id_groupe
            $query = "UPDATE seances SET 
                      jour = ?, 
                      heure_debut = ?, 
                      heure_fin = ?,
                      id_unite_enseignement = ?,
                      type = ?,
                      id_groupe = ?,
                      salle = ?
                      WHERE id = ?";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssissii", $jour, $heure_debut, $heure_fin, $id_ue, $type, $id_groupe, $salle, $id_seance);
        }
    }

    // Exécution de la requête si pas d'erreur
    if (!isset($message)) {
        if ($stmt->execute()) {
            header('Location: gerer_seances.php?message=Séance modifiée avec succès&message_type=success');
            exit();
        } else {
            $message = "Erreur lors de la modification de la séance: " . $stmt->error;
            $message_type = "danger";
        }
    }
}

// Récupérer les informations de la séance
$query_seance = "SELECT s.*, ue.id as id_ue, ue.code as code_ue, ue.intitule as intitule_ue
                 FROM seances s
                 LEFT JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
                 WHERE s.id = ?";
$stmt = $conn->prepare($query_seance);
$stmt->bind_param("i", $id_seance);
$stmt->execute();
$seance = $stmt->get_result()->fetch_assoc();

if (!$seance) {
    header('Location: gerer_seances.php?message=Séance non trouvée&message_type=danger');
    exit();
}

// Récupérer la liste des UE
$query_ue = "SELECT id, code, intitule FROM unites_enseignement ORDER BY code";
$result_ue = $conn->query($query_ue);

// Récupérer la liste des groupes
$query_groupes = "SELECT id, type, numero FROM groupes ORDER BY type, numero";
$result_groupes = $conn->query($query_groupes);
$groupes = [];
while ($groupe = $result_groupes->fetch_assoc()) {
    $groupes[] = $groupe;
}

// Liste des jours de la semaine
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

// Liste des heures possibles
$heures = [];
for ($h = 8; $h <= 18; $h++) {
    for ($m = 0; $m < 60; $m += 30) {
        $heures[] = sprintf("%02d:%02d", $h, $m);
    }
}

// Include header after all potential redirects
require_once "../gestion-module-ue/includes/header.php";
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header">
            <h2>Modifier une séance</h2>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <!-- Jour -->
                <div class="mb-3">
                    <label for="jour" class="form-label">Jour</label>
                    <select class="form-select" id="jour" name="jour" required>
                        <?php foreach ($jours as $jour): ?>
                            <option value="<?php echo $jour; ?>" <?php echo ($seance['jour'] === $jour) ? 'selected' : ''; ?>>
                                <?php echo $jour; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Horaires -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="heure_debut" class="form-label">Heure de début</label>
                        <select class="form-select" id="heure_debut" name="heure_debut" required>
                            <?php foreach ($heures as $heure): ?>
                                <option value="<?php echo $heure; ?>" <?php echo (substr($seance['heure_debut'], 0, 5) === $heure) ? 'selected' : ''; ?>>
                                    <?php echo $heure; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="heure_fin" class="form-label">Heure de fin</label>
                        <select class="form-select" id="heure_fin" name="heure_fin" required>
                            <?php foreach ($heures as $heure): ?>
                                <option value="<?php echo $heure; ?>" <?php echo (substr($seance['heure_fin'], 0, 5) === $heure) ? 'selected' : ''; ?>>
                                    <?php echo $heure; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- UE -->
                <div class="mb-3">
                    <label for="id_ue" class="form-label">Unité d'enseignement</label>
                    <select class="form-select" id="id_ue" name="id_ue" required>
                        <?php while ($ue = $result_ue->fetch_assoc()): ?>
                            <option value="<?php echo $ue['id']; ?>" <?php echo ($seance['id_ue'] == $ue['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Type -->
                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required onchange="toggleGroupeField()">
                        <option value="CM" <?php echo ($seance['type'] === 'CM') ? 'selected' : ''; ?>>CM</option>
                        <option value="TD" <?php echo ($seance['type'] === 'TD') ? 'selected' : ''; ?>>TD</option>
                        <option value="TP" <?php echo ($seance['type'] === 'TP') ? 'selected' : ''; ?>>TP</option>
                    </select>
                </div>

                <!-- Groupe -->
                <div class="mb-3" id="groupe_container">
                    <label for="id_groupe" class="form-label">Groupe</label>
                    <select class="form-select" id="id_groupe" name="id_groupe">
                        <option value="">Sélectionner un groupe</option>
                        <?php foreach ($groupes as $groupe): ?>
                            <option value="<?php echo $groupe['id']; ?>" 
                                    data-type="<?php echo $groupe['type']; ?>"
                                    <?php echo ($seance['id_groupe'] == $groupe['id']) ? 'selected' : ''; ?>>
                                <?php echo $groupe['type'] . ' ' . $groupe['numero']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Veuillez sélectionner un groupe pour les séances de type TD ou TP.
                    </div>
                </div>

                <!-- Salle -->
                <div class="mb-3">
                    <label for="salle" class="form-label">Salle</label>
                    <input type="text" class="form-control" id="salle" name="salle" value="<?php echo htmlspecialchars($seance['salle']); ?>" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="gerer_seances.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleGroupeField() {
    const typeSelect = document.getElementById('type');
    const groupeContainer = document.getElementById('groupe_container');
    const groupeSelect = document.getElementById('id_groupe');
    
    if (typeSelect.value === 'CM') {
        groupeContainer.style.display = 'none';
        groupeSelect.removeAttribute('required');
        groupeSelect.value = '';
    } else {
        groupeContainer.style.display = 'block';
        groupeSelect.setAttribute('required', 'required');
        
        // Filtrer les groupes selon le type sélectionné
        const options = groupeSelect.options;
        let hasValidOption = false;
        
        for (let i = 1; i < options.length; i++) {
            const option = options[i];
            const groupeType = option.getAttribute('data-type');
            
            if (groupeType === typeSelect.value) {
                option.style.display = '';
                hasValidOption = true;
                
                // Si aucun groupe n'est actuellement sélectionné, sélectionner le premier groupe valide
                if (groupeSelect.value === '') {
                    groupeSelect.value = option.value;
                }
            } else {
                option.style.display = 'none';
                if (option.selected) {
                    groupeSelect.value = '';
                }
            }
        }
        
        // Si aucun groupe du type sélectionné n'existe
        if (!hasValidOption) {
            groupeSelect.value = '';
        }
    }
}

// Validation du formulaire côté client
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Appliquer la configuration initiale
        toggleGroupeField();
        
        // Récupérer tous les formulaires
        var forms = document.querySelectorAll('.needs-validation');
        
        // Boucle pour empêcher la soumission si non validé
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                const typeSelect = document.getElementById('type');
                const groupeSelect = document.getElementById('id_groupe');
                
                if (typeSelect.value !== 'CM' && (!groupeSelect.value || groupeSelect.value === '')) {
                    event.preventDefault();
                    event.stopPropagation();
                    groupeSelect.classList.add('is-invalid');
                    return false;
                }
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    });
})();
</script>

<?php require_once "../gestion-module-ue/includes/footer.php"; ?>