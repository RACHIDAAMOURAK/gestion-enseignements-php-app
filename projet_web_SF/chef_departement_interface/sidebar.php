<?php
// Détecter la page actuelle si non définie
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>
<style>
    /* Sidebar styles */
    .sidebar {
        width: 250px;
        background-color: var(--secondary-bg);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        padding: 20px 0;
        border-right: 1px solid var(--border-color);
        z-index: 100;
    }
    
    .sidebar-header {
        display: flex;
        align-items: center;
        padding: 0 20px 20px;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
    }
    
    .sidebar-header i {
        font-size: 24px;
        color: var(--accent-color);
        margin-right: 10px;
    }
    
    .sidebar-header h2 {
        color: var(--accent-color);
        font-size: 18px;
        display: flex;
        align-items: center;
    }
    
    .sidebar-menu {
        list-style: none;
    }
    
    .sidebar-menu li {
        margin-bottom: 5px;
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: var(--text-muted);
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .sidebar-menu a:hover, .sidebar-menu a.active {
        background-color: rgba(49, 183, 209, 0.1);
        color: var(--accent-color);
        border-left: 3px solid var(--accent-color);
    }
    
    .sidebar-menu i {
        margin-right: 10px;
        font-size: 16px;
    }
</style>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-user-tie"></i> Chef Département</h2>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="lister_professeurs_departement.php" class="<?= $current_page == 'lister_professeurs_departement.php' ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                Enseignants du département
            </a>
        </li>
        <li>
            <a href="validation_souhaits.php" class="<?= $current_page == 'validation_souhaits.php' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i>
                Validation des vœux
            </a>
        </li>
        <li>
            <a href="affectation_enseignants_validations_unites_vacantes.php" class="<?= $current_page == 'affectation_enseignants_validations_unites_vacantes.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i>
                Affectation des enseignants/validation des unités vacantes 
            </a>
        </li>
       
        <li>
            <a href="generations_rapports_decisions.php" class="<?= $current_page == 'generations_rapports_decisions.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                rapports et historique des affectations
            </a>
        </li>
        <!-- Lien vers parametres.php (pas encore implémenté) -->
        <li>
            <a href="#" onclick="alert('Cette fonctionnalité n\'est pas encore implémentée.'); return false;" class="<?= $current_page == 'parametres.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                Paramètres
            </a>
        </li>
        <li>
            <a href="logout.php" class="<?= $current_page == 'logout.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </li>
    </ul>
</div>