<?php
// Définir le titre de la page
$page_title = "Consultation des emplois du temps";
// Utiliser le header principal
require_once "../../gestion-module-ue/includes/header.php";
require_once "../includes/config.php";

// Vérification des permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Récupération des filières pour le filtre
$stmt = $conn->prepare("SELECT id, nom FROM filieres ORDER BY nom");
$stmt->execute();
$filieres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Consultation des emplois du temps</h1>
    </div>

    <!-- Filtres -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form id="filtreForm" class="row">
                <div class="col-md-4 mb-3">
                    <label for="filiere" class="form-label">Filière</label>
                    <select class="form-control" id="filiere" name="filiere">
                        <option value="">Toutes les filières</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?php echo $filiere['id']; ?>"><?php echo htmlspecialchars($filiere['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="semestre" class="form-label">Semestre</label>
                    <select class="form-control" id="semestre" name="semestre">
                        <option value="">Tous les semestres</option>
                        <option value="1">Semestre 1</option>
                        <option value="2">Semestre 2</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="vue" class="form-label">Type de vue</label>
                    <select class="form-control" id="vue" name="vue">
                        <option value="filiere">Par filière</option>
                        <option value="enseignant">Par enseignant</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Options d'affichage -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Options d'affichage</h6>
        </div>
        <div class="card-body">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary" id="btnHebdo">
                    <i class="fas fa-calendar-week"></i> Vue hebdomadaire
                </button>
                <button type="button" class="btn btn-secondary" id="btnMensuel">
                    <i class="fas fa-calendar-alt"></i> Vue mensuelle
                </button>
            </div>
        </div>
    </div>

    <!-- Options d'import/export -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Import/Export</h6>
        </div>
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="export.php" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Exporter
                </a>
                <a href="import.php" class="btn btn-info">
                    <i class="fas fa-file-import"></i> Importer
                </a>
            </div>
        </div>
    </div>

    <!-- Conteneur pour l'affichage de l'emploi du temps -->
    <div id="emploiContainer" class="card shadow mb-4">
        <div class="card-body">
            <div class="text-center">
                <p class="text-muted">Sélectionnez des filtres pour afficher l'emploi du temps</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtreForm = document.getElementById('filtreForm');
    const emploiContainer = document.getElementById('emploiContainer');
    const btnHebdo = document.getElementById('btnHebdo');
    const btnMensuel = document.getElementById('btnMensuel');

    // Fonction pour charger l'emploi du temps
    function chargerEmploiDuTemps() {
        const formData = new FormData(filtreForm);
        const vue = formData.get('vue');
        const filiere = formData.get('filiere');
        const semestre = formData.get('semestre');

        if (!filiere || !semestre) {
            emploiContainer.innerHTML = `
                <div class="card-body">
                    <div class="text-center">
                        <p class="text-muted">Veuillez sélectionner une filière et un semestre</p>
                    </div>
                </div>
            `;
            return;
        }

        // Charger la vue appropriée
        const url = vue === 'filiere' ? 'vue_filiere.php' : 'vue_enseignant.php';
        fetch(`${url}?filiere=${filiere}&semestre=${semestre}`)
            .then(response => response.text())
            .then(html => {
                emploiContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Erreur:', error);
                emploiContainer.innerHTML = `
                    <div class="card-body">
                        <div class="text-center">
                            <p class="text-danger">Une erreur est survenue lors du chargement de l'emploi du temps</p>
                        </div>
                    </div>
                `;
            });
    }

    // Écouteurs d'événements
    filtreForm.addEventListener('change', chargerEmploiDuTemps);
    btnHebdo.addEventListener('click', function() {
        btnHebdo.classList.add('btn-primary');
        btnHebdo.classList.remove('btn-secondary');
        btnMensuel.classList.add('btn-secondary');
        btnMensuel.classList.remove('btn-primary');
        chargerEmploiDuTemps();
    });
    btnMensuel.addEventListener('click', function() {
        btnMensuel.classList.add('btn-primary');
        btnMensuel.classList.remove('btn-secondary');
        btnHebdo.classList.add('btn-secondary');
        btnHebdo.classList.remove('btn-primary');
        chargerEmploiDuTemps();
    });
});
</script>

<?php require_once "../../gestion-module-ue/includes/footer.php"; ?> 