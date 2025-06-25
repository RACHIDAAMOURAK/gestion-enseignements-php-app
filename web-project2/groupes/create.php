<?php
require_once "../gestion-module-ue/includes/header.php";
require_once "../gestion-module-ue/includes/db.php";

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

// Récupérer la liste des UE avec leurs filières associées
$query_ue = "SELECT u.id, u.code, u.intitule, u.id_filiere, f.nom as filiere_nom
             FROM unites_enseignement u
             LEFT JOIN filieres f ON u.id_filiere = f.id";

// Filter UEs by the coordinator's filiere if applicable
if ($coordinateur_filiere_id !== null) {
    $query_ue .= " WHERE u.id_filiere = '" . mysqli_real_escape_string($conn, $coordinateur_filiere_id) . "'";
}

$query_ue .= " ORDER BY u.code";

$result_ue = mysqli_query($conn, $query_ue);

// Créer un tableau associatif des UE et leurs filières pour JavaScript
$ue_filieres = array();
while ($ue = mysqli_fetch_assoc($result_ue)) {
    // Only add UEs with a valid filiere for the dropdown logic
    if ($ue['id_filiere'] !== null) {
        $ue_filieres[] = array(
            'id' => $ue['id'],
            'filiere_id' => $ue['id_filiere'],
            'filiere_nom' => $ue['filiere_nom']
        );
    }
}
?>

<div class="container mt-4">
    <h2>Créer un nouveau groupe</h2>
    
    <form action="process_create.php" method="POST" class="mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="id_unite_enseignement" class="form-label">Unité d'Enseignement</label>
                <select class="form-select" id="id_unite_enseignement" name="id_unite_enseignement" required>
                    <option value="">Sélectionnez une UE</option>
                    <?php 
                    mysqli_data_seek($result_ue, 0);
                    while ($ue = mysqli_fetch_assoc($result_ue)): 
                    ?>
                        <option value="<?= $ue['id'] ?>">
                            <?= htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="id_filiere" class="form-label">Filière</label>
                <select class="form-select" id="id_filiere" name="id_filiere" required disabled>
                    <option value="">Sélectionnez d'abord une UE</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type" required>
                <option value="TD">TD</option>
                <option value="TP">TP</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="numero" class="form-label">Numéro du Groupe</label>
            <input type="number" class="form-control" id="numero" name="numero" required min="1">
        </div>

        <div class="mb-3">
            <label for="effectif" class="form-label">Effectif</label>
            <input type="number" class="form-control" id="effectif" name="effectif" required min="1">
        </div>

        <div class="mb-3">
            <label for="annee_universitaire" class="form-label">Année Universitaire</label>
            <input type="text" class="form-control" id="annee_universitaire" name="annee_universitaire" 
                   required pattern="\d{4}-\d{4}" placeholder="2023-2024">
        </div>

        <div class="mb-3">
            <label for="semestre" class="form-label">Semestre</label>
            <select class="form-select" id="semestre" name="semestre" required>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>">Semestre <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Créer le groupe</button>
            <a href="index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
// Données des UE et leurs filières associées
const ueFilieres = <?php echo json_encode($ue_filieres); ?>;

// Fonction pour mettre à jour les filières disponibles
function updateFilieres() {
    const ueSelect = document.getElementById('id_unite_enseignement');
    const filiereSelect = document.getElementById('id_filiere');
    
    // Réinitialiser et désactiver le select des filières
    filiereSelect.innerHTML = '<option value="">Sélectionnez d\'abord une UE</option>';
    filiereSelect.disabled = true;

    // Si une UE est sélectionnée
    if (ueSelect.value) {
        const selectedUE = ueFilieres.find(ue => ue.id === ueSelect.value);
        if (selectedUE) {
            // Activer le select des filières
            filiereSelect.disabled = false;
            filiereSelect.innerHTML = '<option value="">Sélectionnez une filière</option>';
            
            // Ajouter les filières associées à l'UE
            const option = document.createElement('option');
            option.value = selectedUE.filiere_id;
            option.textContent = selectedUE.filiere_nom;
            filiereSelect.appendChild(option);
        }
    }
}

// Écouter les changements sur le select des UE
document.getElementById('id_unite_enseignement').addEventListener('change', updateFilieres);
</script>

<?php require_once "../gestion-module-ue/includes/footer.php"; ?> 
