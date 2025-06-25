<?php
// Connexion à la base de données
$page_title = "Ajouter un vacataire";
require_once "../gestion-module-ue/includes/header.php";

// Inclure le fichier CSS spécifique au module vacataire
echo '<link rel="stylesheet" href="style.css">';


// Récupérer les départements et spécialités
try {
    $stmt = $conn->prepare("SELECT id, nom FROM departements ORDER BY nom");
    $stmt->execute();
    $departements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, nom FROM specialites ORDER BY nom");
    $stmt->execute();
    $specialites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de chargement des données: " . $e->getMessage();
}
?>
<style>
     :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
        }

    </style>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary bg-gradient text-white">
                    <h4 class="mb-0">Créer un compte vacataire</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <h5 class="alert-heading">Compte vacataire créé avec succès !</h5>
                            <p>Voici les informations de connexion :</p>
                            <ul class="mb-0">
                                <li>Nom d'utilisateur : <?= isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '' ?></li>
                                <li>Email : <?= isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '' ?></li>
                                <li>Mot de passe : <strong><?= isset($_GET['password']) ? htmlspecialchars($_GET['password']) : '' ?></strong></li>
                            </ul>
                            <hr>
                            <p class="mb-0">Veuillez noter ces informations, le mot de passe ne sera plus accessible ultérieurement.</p>
                        </div>
                    <?php endif; ?>
                    <form action="enregistrer_vacataire.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nom :</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom :</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spécialité :</label>
                            <select name="specialite" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($specialites as $spec): ?>
                                    <option value="<?= htmlspecialchars($spec['nom']) ?>"><?= htmlspecialchars($spec['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Département :</label>
                            <select name="id_departement" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($departements as $dep): ?>
                                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Créer le compte vacataire</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Activer les sous-menus
    document.addEventListener('DOMContentLoaded', function() {
        const submenuItems = document.querySelectorAll('.menu-item.has-submenu');
        submenuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target === this || e.target.parentElement === this) {
                    e.preventDefault();
                    this.classList.toggle('open');
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('show');
                    }
                }
            });
        });
        
        // Ouvrir automatiquement le sous-menu actif
        const activeSubItems = document.querySelectorAll('.submenu .menu-item.active');
        activeSubItems.forEach(item => {
            const parent = item.closest('.submenu');
            if (parent) {
                parent.classList.add('show');
                const trigger = parent.previousElementSibling;
                if (trigger && trigger.classList.contains('has-submenu')) {
                    trigger.classList.add('open');
                }
            }
        });
    });
</script>
<?php
// Fermeture du conteneur principal et inclusion du pied de page
echo '</div></div></div>';
require_once "../gestion-module-ue/includes/footer.php";
?> 