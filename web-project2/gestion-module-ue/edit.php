<?php
require_once 'includes/db.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Récupérer les détails de l'UE
$query = "SELECT * FROM unites_enseignement WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$ue = mysqli_fetch_assoc($result);

// Récupérer la liste des départements
$query_departements = "SELECT * FROM departements ORDER BY nom";
$result_departements = mysqli_query($conn, $query_departements);

// Récupérer la liste des spécialités
$query_specialites = "SELECT * FROM specialites ORDER BY nom";
$result_specialites = mysqli_query($conn, $query_specialites);

// Récupérer la liste des enseignants
$query_enseignants = "SELECT id, nom, prenom 
                     FROM utilisateurs 
                     WHERE role IN ('enseignant', 'chef_departement', 'coordonnateur') 
                     ORDER BY nom, prenom";
$result_enseignants = mysqli_query($conn, $query_enseignants);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $intitule = mysqli_real_escape_string($conn, $_POST['intitule']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $specialite = mysqli_real_escape_string($conn, $_POST['specialite']);
    $id_departement = mysqli_real_escape_string($conn, $_POST['id_departement']);
    $semestre = mysqli_real_escape_string($conn, $_POST['semestre']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);
    $volume_cm = mysqli_real_escape_string($conn, $_POST['volume_horaire_cm']);
    $volume_td = mysqli_real_escape_string($conn, $_POST['volume_horaire_td']);
    $volume_tp = mysqli_real_escape_string($conn, $_POST['volume_horaire_tp']);
    $id_responsable = !empty($_POST['id_responsable']) ? mysqli_real_escape_string($conn, $_POST['id_responsable']) : "NULL";
    $statut = mysqli_real_escape_string($conn, $_POST['statut']);

    $update_query = "UPDATE unites_enseignement SET 
                    code = '$code',
                    intitule = '$intitule',
                    description = '$description',
                    specialite = '$specialite',
                    id_departement = '$id_departement',
                    semestre = '$semestre',
                    credits = '$credits',
                    volume_horaire_cm = '$volume_cm',
                    volume_horaire_td = '$volume_td',
                    volume_horaire_tp = '$volume_tp',
                    id_responsable = $id_responsable,
                    statut = '$statut'
                    WHERE id = '$id'";

    if (mysqli_query($conn, $update_query)) {
        header('Location: index.php');
        exit;
    }
}

// Ici SEULEMENT, on inclut le header HTML
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>Modifier l'UE : <?= htmlspecialchars($ue['code']) ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="code" name="code" value="<?= htmlspecialchars($ue['code']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="intitule" class="form-label">Intitulé</label>
                        <input type="text" class="form-control" id="intitule" name="intitule" value="<?= htmlspecialchars($ue['intitule']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_departement" class="form-label">Département</label>
                        <select class="form-select" id="id_departement" name="id_departement" required>
                            <?php while ($dept = mysqli_fetch_assoc($result_departements)): ?>
                                <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $ue['id_departement'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['nom']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="specialite" class="form-label">Spécialité</label>
                        <select class="form-select" id="specialite" name="specialite" required>
                            <?php while ($spec = mysqli_fetch_assoc($result_specialites)): ?>
                                <option value="<?= htmlspecialchars($spec['nom']) ?>" <?= $spec['nom'] == $ue['specialite'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($spec['nom']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="semestre" class="form-label">Semestre</label>
                        <select class="form-select" id="semestre" name="semestre" required>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == $ue['semestre'] ? 'selected' : '' ?>>
                                    Semestre <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="credits" class="form-label">Crédits</label>
                        <input type="number" class="form-control" id="credits" name="credits" value="<?= htmlspecialchars($ue['credits']) ?>" required min="1" max="30">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="volume_horaire_cm" class="form-label">Volume horaire CM</label>
                        <input type="number" class="form-control" id="volume_horaire_cm" name="volume_horaire_cm" value="<?= htmlspecialchars($ue['volume_horaire_cm']) ?>" required min="0" step="0.5">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="volume_horaire_td" class="form-label">Volume horaire TD</label>
                        <input type="number" class="form-control" id="volume_horaire_td" name="volume_horaire_td" value="<?= htmlspecialchars($ue['volume_horaire_td']) ?>" required min="0" step="0.5">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="volume_horaire_tp" class="form-label">Volume horaire TP</label>
                        <input type="number" class="form-control" id="volume_horaire_tp" name="volume_horaire_tp" value="<?= htmlspecialchars($ue['volume_horaire_tp']) ?>" required min="0" step="0.5">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_responsable" class="form-label">Responsable de l'UE</label>
                        <select class="form-select" id="id_responsable" name="id_responsable">
                            <option value="">Sélectionner un responsable</option>
                            <?php while ($ens = mysqli_fetch_assoc($result_enseignants)): ?>
                                <option value="<?= $ens['id'] ?>" <?= $ens['id'] == $ue['id_responsable'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ens['nom'] . ' ' . $ens['prenom']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select" id="statut" name="statut" required>
                            <option value="disponible" <?= $ue['statut'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="affecte" <?= $ue['statut'] == 'affecte' ? 'selected' : '' ?>>Affecté</option>
                            <option value="vacant" <?= $ue['statut'] == 'vacant' ? 'selected' : '' ?>>Vacant</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($ue['description']) ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 