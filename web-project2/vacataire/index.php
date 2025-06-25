<?php
// Définir le titre de la page
$page_title = "Gestion des Vacataires";
require_once "../gestion-module-ue/includes/header.php";

// Inclure le fichier CSS spécifique au module vacataire
echo '<link rel="stylesheet" href="style.css">';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary bg-gradient text-white">
                    <h4 class="mb-0">Gestion des Vacataires</h4>
                </div>
                <div class="card-body">
                    <p>Bienvenue dans le module de gestion des vacataires. Ce module vous permet de créer des comptes pour les enseignants vacataires et de leur affecter des unités d'enseignement.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-primary">
                        <i class="fas fa-user-plus me-2"></i>Ajouter un vacataire
                    </h5>
                    <p class="card-text flex-grow-1">Créez un nouveau compte pour un enseignant vacataire en spécifiant ses informations personnelles et professionnelles.</p>
                    <a href="creer_vacataire.php" class="btn btn-primary mt-auto">
                        <i class="fas fa-plus-circle me-1"></i> Créer un compte
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-primary">
                        <i class="fas fa-tasks me-2"></i>Affectation des UEs
                    </h5>
                    <p class="card-text flex-grow-1">Affectez des unités d'enseignement vacantes aux vacataires et suivez l'historique des affectations.</p>
                    <a href="liste_ue_vacant.php" class="btn btn-primary mt-auto">
                        <i class="fas fa-link me-1"></i> Gérer les affectations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

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