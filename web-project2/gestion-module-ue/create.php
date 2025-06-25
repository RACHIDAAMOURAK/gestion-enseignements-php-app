<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est un coordonnateur
if ($_SESSION['role'] !== 'coordonnateur') {
    $_SESSION['error'] = "Seuls les coordonnateurs peuvent créer des unités d'enseignement.";
    header('Location: index.php');
    exit;
}

// Récupérer la filière du coordonnateur
$user_id = $_SESSION['user_id'];
$query_filiere = "SELECT id_filiere FROM utilisateurs WHERE id = ? AND role = 'coordonnateur'";
$stmt = mysqli_prepare($conn, $query_filiere);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_filiere = mysqli_stmt_get_result($stmt);
$filiere = mysqli_fetch_assoc($result_filiere);

if (!$filiere || !$filiere['id_filiere']) {
    $_SESSION['error'] = "Vous devez être associé à une filière pour créer une unité d'enseignement.";
    header('Location: index.php');
    exit;
}

// Récupération des données pour les listes déroulantes
$query_departements = "SELECT id, nom FROM departements ORDER BY nom";
$result_departements = mysqli_query($conn, $query_departements);

$query_specialites = "SELECT id, nom FROM specialites ORDER BY nom";
$result_specialites = mysqli_query($conn, $query_specialites);

// Modification de la requête pour utiliser la table utilisateurs
$query_enseignants = "SELECT id, CONCAT(nom, ' ', prenom) as nom_complet 
                     FROM utilisateurs 
                     WHERE role IN ('enseignant', 'vacataire') 
                     ORDER BY nom";
$result_enseignants = mysqli_query($conn, $query_enseignants);

$annees = ['2023-2024', '2024-2025', '2025-2026'];
?>

<div class="container mt-4">
    <h2 class="mb-4">Créer une Unité d'Enseignement</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form action="process_create.php" method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="id_filiere" value="<?= $filiere['id_filiere'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="code" class="form-label">Code UE*</label>
                <input type="text" class="form-control" id="code" name="code" 
                       pattern="[A-Z]{2}\d{3}" title="2 lettres majuscules suivies de 3 chiffres (ex: IN101)" required>
                <div class="invalid-feedback">Veuillez entrer un code valide (ex: IN101)</div>
            </div>
            
            <div class="col-md-6">
                <label for="intitule" class="form-label">Intitulé UE*</label>
                <input type="text" class="form-control" id="intitule" name="intitule" required maxlength="100">
            </div>
            
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="col-md-4">
                <label for="credits" class="form-label">Crédits*</label>
                <input type="number" class="form-control" id="credits" name="credits" min="1" required>
            </div>
            
            <div class="col-md-4">
                <label for="id_departement" class="form-label">Département*</label>
                <select class="form-select" id="id_departement" name="id_departement" required>
                    <option value="">Sélectionnez un département</option>
                    <?php while($departement = mysqli_fetch_assoc($result_departements)): ?>
                        <option value="<?= $departement['id'] ?>"><?= htmlspecialchars($departement['nom']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="specialite" class="form-label">Spécialité*</label>
                <select class="form-select" id="specialite" name="specialite" required>
                    <option value="">Sélectionnez une spécialité</option>
                    <?php while($specialite = mysqli_fetch_assoc($result_specialites)): ?>
                        <option value="<?= htmlspecialchars($specialite['nom']) ?>"><?= htmlspecialchars($specialite['nom']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="semestre" class="form-label">Semestre*</label>
                <select class="form-select" id="semestre" name="semestre" required>
                    <option value="1">S1</option>
                    <option value="2">S2</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="annee_universitaire" class="form-label">Année*</label>
                <select class="form-select" id="annee_universitaire" name="annee_universitaire" required>
                    <?php foreach ($annees as $annee): ?>
                        <option value="<?= $annee ?>"><?= $annee ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="volume_horaire_cm" class="form-label">Volume CM (h)*</label>
                <input type="number" class="form-control" id="volume_horaire_cm" name="volume_horaire_cm" step="0.5" min="0" required>
            </div>
            
            <div class="col-md-4">
                <label for="volume_horaire_td" class="form-label">Volume TD (h)</label>
                <input type="number" class="form-control" id="volume_horaire_td" name="volume_horaire_td" step="0.5" min="0">
            </div>
            
            <div class="col-md-4">
                <label for="volume_horaire_tp" class="form-label">Volume TP (h)</label>
                <input type="number" class="form-control" id="volume_horaire_tp" name="volume_horaire_tp" step="0.5" min="0">
            </div>
            
            <div class="col-md-6">
                <label for="id_responsable" class="form-label">Enseignant responsable*</label>
                <select class="form-select" id="id_responsable" name="id_responsable" required>
                    <option value="">Sélectionnez un enseignant</option>
                    <?php while($enseignant = mysqli_fetch_assoc($result_enseignants)): ?>
                        <option value="<?= $enseignant['id'] ?>"><?= htmlspecialchars($enseignant['nom_complet']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <div class="form-check mt-4 pt-3">
                    <input class="form-check-input" type="checkbox" id="statut" name="statut" value="disponible" checked>
                    <label class="form-check-label" for="statut">
                        UE disponible pour affectation
                    </label>
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>