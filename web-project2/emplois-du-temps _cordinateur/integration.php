<?php
/**
 * Fichier modèle pour l'intégration des pages d'emplois du temps 
 * dans le système de menu principal
 * 
 * Instructions pour adapter un fichier PHP existant:
 * 
 * 1. Ajouter au début du fichier:
 * $page_title = "Titre de la page";
 * require_once "../gestion-module-ue/includes/header.php";
 * require_once "./includes/config.php";
 * 
 * 2. Retirer toute référence à "./includes/header.php"
 * 
 * 3. Remplacer à la fin du fichier:
 * require_once "./includes/footer.php"
 * par:
 * require_once "../gestion-module-ue/includes/footer.php";
 * 
 * 4. Ajouter des classes pour harmoniser le style:
 * - Utiliser .card pour les conteneurs principaux
 * - Utiliser .card-header pour les en-têtes de section
 * - Utiliser .card-body pour le contenu
 * - Utiliser les couleurs des variables CSS (--primary-bg, --secondary-bg, --accent-color)
 */

// Exemple d'utilisation:
$page_title = "Nom de votre page";
require_once "../gestion-module-ue/includes/header.php";
require_once "./includes/config.php";
?>

<div class="container mt-4">
    <h2>Titre de la page</h2>
    
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>Section principale</h5>
        </div>
        <div class="card-body">
            <!-- Contenu de la page ici -->
            <p>Contenu à remplacer...</p>
        </div>
    </div>
</div>

<?php
require_once "../gestion-module-ue/includes/footer.php";
?> 