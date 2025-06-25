<?php
require_once "../gestion-module-ue/includes/header.php";
require_once "../gestion-module-ue/includes/db.php";

// Récupérer la liste des UE
$query_ue = "SELECT id, code, intitule FROM unites_enseignement ORDER BY code";
$result_ue = mysqli_query($conn, $query_ue);

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM groupes WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $groupe = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['error'] = "Groupe non trouvé.";
        header("Location: index.php");
        exit();
    }
} else {
    $_SESSION['error'] = "ID non spécifié.";
    header("Location: index.php");
    exit();
}
?>

<div class="container mt-4">
    <h2>Modifier le Groupe</h2>
    
    <form action="process_edit.php" method="POST" class="mt-4">
        <input type="hidden" name="id" value="<?= $groupe['id'] ?>">
        
        <div class="mb-3">
            <label for="id_unite_enseignement" class="form-label">Unité d'Enseignement</label>
            <select class="form-select" id="id_unite_enseignement" name="id_unite_enseignement" required>
                <?php while ($ue = mysqli_fetch_assoc($result_ue)): ?>
                    <option value="<?= $ue['id'] ?>" <?= ($ue['id'] == $groupe['id_unite_enseignement']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type" required>
                <option value="TD" <?= ($groupe['type'] == 'TD') ? 'selected' : '' ?>>TD</option>
                <option value="TP" <?= ($groupe['type'] == 'TP') ? 'selected' : '' ?>>TP</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="numero" class="form-label">Numéro du Groupe</label>
            <input type="number" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($groupe['numero']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="effectif" class="form-label">Effectif</label>
            <input type="number" class="form-control" id="effectif" name="effectif" value="<?= htmlspecialchars($groupe['effectif']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="annee_universitaire" class="form-label">Année Universitaire</label>
            <input type="text" class="form-control" id="annee_universitaire" name="annee_universitaire" value="<?= htmlspecialchars($groupe['annee_universitaire']) ?>" required placeholder="ex: 2023-2024">
        </div>

        <div class="mb-3">
            <label for="semestre" class="form-label">Semestre</label>
            <select class="form-select" id="semestre" name="semestre" required>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>" <?= ($groupe['semestre'] == $i) ? 'selected' : '' ?>>
                        Semestre <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require_once "../gestion-module-ue/includes/footer.php"; ?>
