<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Récupérer les informations de l'utilisateur pour l'affichage
$user_display_name = isset($_SESSION['nom_complet']) ? $_SESSION['nom_complet'] : 'Utilisateur';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Déterminer les initiales pour l'avatar
$avatar_initials = '??'; // Valeur par défaut si rien n'est trouvé

if ($user_role === 'admin') {
    $avatar_initials = 'AD';
} elseif (!empty($user_display_name)) {
    $words = explode(' ', $user_display_name);
    if (count($words) >= 2) {
        $avatar_initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $avatar_initials = strtoupper(substr($user_display_name, 0, 2));
    }
} elseif (!empty($user_role)) {
    $avatar_initials = strtoupper(substr($user_role, 0, 2));
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Administration'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
            --header-bg: var(--secondary-bg);
            --top-nav-height: 60px;
            --footer-height: 50px;
            --sidebar-width-collapsed: 50px;
            --sidebar-width-expanded: 250px;
            /* Variables pour les espacements */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --content-padding: var(--spacing-md);
            --section-margin: var(--spacing-md);
            --card-padding: var(--spacing-md);
            --form-spacing: var(--spacing-md);
        }

        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width-collapsed);
            background-color: var(--primary-bg);
            border-right: 1px solid var(--border-color);
            transition: width 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        body.menu-expanded .sidebar {
            width: var(--sidebar-width-expanded);
        }

        .sidebar-logo {
            padding: 1.5rem 0;
            color: var(--accent-color);
            text-align: center;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-items {
            display: flex;
            flex-direction: column;
            height: calc(100% - 60px);
            padding: 1rem 0;
            justify-content: space-between;
        }

        .menu-items-top {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-items-bottom {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-item {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            white-space: nowrap;
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8A9CC9;
        }

        .menu-toggle {
            color: var(--accent-color);
            text-align: center;
            padding: 1.2rem 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            margin-top: 0.5rem;
            width: 100%;
        }

        .menu-toggle i {
            font-size: 1.5rem;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-toggle:hover i {
            transform: rotate(180deg);
        }

        body.menu-expanded .menu-toggle i {
            transform: rotate(-180deg);
        }

        body.menu-expanded .menu-toggle:hover i {
            transform: rotate(-360deg);
        }

        .menu-toggle .menu-text {
            display: none;
        }

        body.menu-expanded .menu-toggle {
            justify-content: flex-start;
            padding-left: 1.5rem;
        }

        .menu-item:hover, .menu-item.active {
            color: var(--text-color);
            background-color: rgba(49, 183, 209, 0.1);
        }

        .menu-item.active {
            background-color: var(--secondary-bg);
            border-left: 3px solid var(--accent-color);
        }

        .menu-item:hover i, .menu-item.active i {
            color: var(--accent-color) !important;
        }

        .menu-text {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            font-size: 0.95rem;
        }

        body.menu-expanded .menu-text {
            opacity: 1;
            visibility: visible;
        }

        .top-nav {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width-collapsed);
            z-index: 999;
            height: var(--top-nav-height);
            background-color: var(--primary-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 var(--spacing-md);
            transition: left 0.3s ease;
        }

        body.menu-expanded .top-nav {
            left: var(--sidebar-width-expanded);
        }

        .container-fluid {
            width: 100%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            justify-content: flex-end;
            width: 100%;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .user-profile:hover {
            background-color: rgba(49, 183, 209, 0.1);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            color: var(--text-color);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .user-role {
            color: #8A9CC9;
            font-size: 0.75rem;
        }

        .action-button {
            color: #8A9CC9;
            font-size: 1.1rem;
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .action-button:hover {
            color: var(--accent-color);
            background-color: rgba(49, 183, 209, 0.1);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .logout-button {
            color: #8A9CC9;
            background: transparent;
            border: none;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .logout-button:hover {
            color: var(--accent-color);
            background-color:rgba(74, 71, 71, 0.14);
        }

        .main-container {
            margin-left: var(--sidebar-width-collapsed);
            padding-top: var(--top-nav-height);
            padding-bottom: var(--footer-height);
            flex: 1;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            overflow-y: auto;
        }

        body.menu-expanded .main-container {
            margin-left: var(--sidebar-width-expanded);
        }

        .content {
            padding: var(--content-padding);
            min-height: calc(100vh - var(--top-nav-height) - var(--footer-height));
        }

        .footer {
            background-color: var(--primary-bg);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            position: fixed;
            bottom: 0;
            right: 0;
            left: var(--sidebar-width-collapsed);
            z-index: 999;
            transition: left 0.3s ease;
            height: var(--footer-height);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.menu-expanded .footer {
            left: var(--sidebar-width-expanded);
        }

        .footer p {
            color: #8A9CC9;
            font-size: 0.875rem;
            margin: 0;
        }

        .footer span {
            color: var(--text-color);
            margin: 0 0.5rem;
        }

        /* Styles pour les sections communes */
        .section-header {
            margin-bottom: var(--section-margin);
        }

        .card {
            margin-bottom: var(--section-margin);
            padding: var(--card-padding);
        }

        .form-group {
            margin-bottom: var(--form-spacing);
        }

        .row {
            margin-bottom: var(--spacing-md);
        }

        .mb-4 {
            margin-bottom: var(--spacing-lg) !important;
        }

        .mt-4 {
            margin-top: var(--spacing-lg) !important;
        }

        .p-4 {
            padding: var(--spacing-lg) !important;
        }

        .alert {
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
        }

        .table {
            margin-bottom: var(--spacing-lg);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            margin: var(--spacing-xs);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            body.menu-expanded .sidebar {
                width: var(--sidebar-width-expanded);
                transform: translateX(0);
            }

            .top-nav {
                left: 0;
            }

            body.menu-expanded .top-nav {
                left: var(--sidebar-width-expanded);
            }

            .main-container {
                margin-left: 0;
                padding-left: 15px;
                padding-right: 15px;
            }

            body.menu-expanded .main-container {
                margin-left: var(--sidebar-width-expanded);
            }

            .footer {
                left: 0;
            }

            body.menu-expanded .footer {
                left: var(--sidebar-width-expanded);
            }

            :root {
                --content-padding: var(--spacing-sm);
                --section-margin: var(--spacing-sm);
                --card-padding: var(--spacing-sm);
            }
        }

        /* Ajustements pour les grands écrans */
        @media (min-width: 1400px) {
            .main-container .container-fluid {
                max-width: 1320px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="menu-items">
            <div class="menu-items-top">
                <a href="../admin/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Tableau de bord</span>
                </a>
                
                <a href="../admin/departments.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span class="menu-text">Départements</span>
                </a>
                
                <a href="../admin/roles.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span class="menu-text">Rôles</span>
                </a>
                
                <a href="../admin/settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Paramètres</span>
                </a>
            </div>

            <div class="menu-items-bottom">
                <a href="../admin/profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">Mon profil</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid">
            <div class="user-menu">
                <div class="action-button">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                    <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </div>
                <a href="../admin/profile.php" class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper($avatar_initials); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_display_name); ?></span>
                        <span class="user-role"><?php echo ucfirst($user_role); ?></span>
                    </div>
                </a>
                <a href="../logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
    
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>
                &copy; <?php echo date('Y'); ?> Système de Gestion
                <span>|</span>
                <span style="color: #8A9CC9;">Version 1.0.0</span>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner le bouton de menu et vérifier qu'il existe
    const menuToggle = document.getElementById('menu-toggle');
    
    if (!menuToggle) {
        console.error("Élément menu-toggle non trouvé!");
        return;
    }
    
    // Vérifier si le corps a déjà la classe menu-expanded (état sauvegardé)
    const savedMenuState = localStorage.getItem('menuExpanded');
    if (savedMenuState === 'true') {
        document.body.classList.add('menu-expanded');
    }
    
    // Fonction pour basculer l'état du menu
    function toggleMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        const isExpanded = document.body.classList.toggle('menu-expanded');
        
        // Sauvegarder l'état du menu
        localStorage.setItem('menuExpanded', isExpanded);
        
        // Déboguer
        console.log('État du menu: ' + (isExpanded ? 'ouvert' : 'fermé'));
    }
    
    // Fonction pour fermer le menu
    function closeMenu() {
        document.body.classList.remove('menu-expanded');
        localStorage.setItem('menuExpanded', false);
    }
    
    // Attacher l'événement click au bouton de menu
    menuToggle.addEventListener('click', toggleMenu);
    
    // Empêcher la fermeture quand on clique sur le sidebar
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Fermer le menu quand on clique en dehors
    document.addEventListener('click', function(e) {
        // et pas quand on clique sur le bouton toggle
        if (document.body.classList.contains('menu-expanded') && 
            sidebar && !sidebar.contains(e.target) && 
            e.target !== menuToggle) {
            closeMenu();
        }
    });
});
    </script>
</body>
</html>