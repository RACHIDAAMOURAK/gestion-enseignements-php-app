<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Récupérer le nom complet de l'utilisateur depuis la session
$user_fullname = isset($_SESSION['nom_complet']) ? $_SESSION['nom_complet'] : 'Coordonnateur';

// --- Notifications pour le coordinateur ---
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$notifications = [];
$notifications_count = 0;
if ($user_id) {
    $notif_query = "SELECT * FROM Notifications_Coordonnateur WHERE id_utilisateur = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
    if ($stmt = $conn->prepare($notif_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $notifications_count = count($notifications);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Espace Coordonnateur'; ?></title>
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
            --sidebar-width-collapsed: 65px;
            --sidebar-width-expanded: 280px;
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
            padding: 0;
            justify-content: space-between;
        }

        .menu-items-top {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .menu-items-bottom {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .menu-item {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 24px;
            min-width: 24px;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        .card, .stat-card {
            background: var(--secondary-bg);
            color: var(--text-color);
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
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
            background: var(--secondary-bg);
            color: var(--text-color);
            margin-bottom: var(--spacing-lg);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            margin: var(--spacing-xs);
        }

        input, select {
            background: #202a3c;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        input::placeholder {
            color: var(--text-muted);
        }

        .btn, .action-button {
            background: transparent;
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        .btn:hover, .action-button:hover {
            background: var(--accent-color);
            color: #fff;
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

        /* Styles pour les sous-menus */
        .menu-item.has-submenu {
            position: relative;
            cursor: pointer;
        }

        .menu-item.has-submenu .submenu-icon {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .menu-item.has-submenu.open .submenu-icon {
            transform: rotate(180deg);
        }

        .submenu {
            display: none;
            background-color: rgba(31, 41, 55, 0.5);
            overflow: hidden;
            padding-left: 0.5rem;
        }

        .submenu.show {
            display: block;
        }

        .submenu-item {
            padding-left: 2.25rem;
            font-size: 0.9rem;
        }

        .menu-items-all {
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 0;
        }

        .menu-items-all > a,
        .menu-items-all > .submenu {
            margin-bottom: 0;
            border-bottom: 1px solid rgba(108, 139, 189, 0.1);
        }

        .menu-items-all > a:last-child {
            border-bottom: none;
        }

        /* Style pour le texte du menu consultation */
        .consultation-text {
            font-size: 0.93rem;
        }

        /* Ajustement des sous-menus */
        body.menu-expanded .submenu {
            width: calc(var(--sidebar-width-expanded) - 10px);
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
                <a href="/projet_web/web-project2/gestion-module-ue/index.php" class="menu-item <?php echo (strpos($_SERVER['PHP_SELF'], 'gestion-module-ue') !== false && (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'create.php')) ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span class="menu-text">Gestion des UE</span>
                </a>
                
                <a href="/projet_web/web-project2/groupes/index.php" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'groupes') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">Gestion des Groupes</span>
                </a>
                
                <!-- Menu consultation avec dropdown -->
                <a href="javascript:void(0);" class="menu-item has-submenu <?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" id="consultation-menu">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="menu-text consultation-text">Consulter l'emploi du temps</span>
                    <i class="fas fa-chevron-down ms-auto submenu-icon"></i>
                </a>
                <div class="submenu">
                    <a href="/projet_web/web-project2/emplois-du-temps _cordinateur/consultation/vue_filiere.php" class="menu-item submenu-item <?php echo strpos($_SERVER['PHP_SELF'], 'vue_filiere.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap"></i>
                        <span class="menu-text">Par semestre</span>
                    </a>
                    <a href="/projet_web/web-project2/emplois-du-temps _cordinateur/consultation/vue_enseignant.php" class="menu-item submenu-item <?php echo strpos($_SERVER['PHP_SELF'], 'vue_enseignant.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span class="menu-text">Par enseignant</span>
                    </a>
                </div>
                
                <a href="/projet_web/web-project2/emplois-du-temps _cordinateur/gerer_seances.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'gerer_seances.php' || basename($_SERVER['PHP_SELF']) == 'ajouter_seance.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span class="menu-text">Gestion des séances</span>
                </a>
                
                <a href="/projet_web/web-project2/emplois-du-temps _cordinateur/gerer_emplois.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'gerer_emplois.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Gestion des emplois du temps</span>
                </a>
                
                <!-- Gestion des vacataires avec dropdown -->
                <a href="javascript:void(0);" class="menu-item has-submenu <?php echo strpos($_SERVER['PHP_SELF'], 'vacataire') !== false ? 'active' : ''; ?>" id="vacataire-menu">
                    <i class="fas fa-user-plus"></i>
                    <span class="menu-text">Gestion des vacataires</span>
                    <i class="fas fa-chevron-down ms-auto submenu-icon"></i>
                </a>
                <div class="submenu" id="vacataire-submenu">
                    <a href="/projet_web/web-project2/vacataire/creer_vacataire.php" class="menu-item submenu-item <?php echo strpos($_SERVER['PHP_SELF'], 'creer_vacataire.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span class="menu-text">Ajouter un vacataire</span>
                    </a>
                    <a href="/projet_web/web-project2/vacataire/liste_ue_vacant.php" class="menu-item submenu-item <?php echo strpos($_SERVER['PHP_SELF'], 'liste_ue_vacant.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span class="menu-text">Affectation des UE</span>
                    </a>
                </div>

                <a href="/projet_web/web-project2/profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span class="menu-text">Mon profil</span>
                </a>
                <a href="/projet_web/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Déconnexion</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid">
            <div class="user-menu">
                <div class="action-button notification-icon" style="position:relative;">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count > 0): ?>
                        <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                    <div class="notification-dropdown" style="display:none; position:absolute; right:0; top:40px; background:var(--primary-bg); color:var(--text-color); border-radius:8px; min-width:240px; box-shadow:0 2px 8px #0003; z-index:100;">
                        <?php if ($notifications_count == 0): ?>
                            <div style="padding:10px;">Aucune notification</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div style="padding:10px; border-bottom:1px solid var(--border-color); font-size:0.95em;">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                    <span style="float:right; color:var(--text-muted); font-size:0.8em;">
                                        <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="/projet_web/web-project2/profile.php" class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_fullname, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo $user_fullname; ?></span>
                        <span class="user-role">Coordonnateur</span>
                    </div>
                </a>
                <a href="/projet_web/logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="container-fluid">
            <div class="content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>


</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var notifIcon = document.querySelector('.notification-icon');
    var notifDropdown = document.querySelector('.notification-dropdown');
    if (notifIcon && notifDropdown) {
        notifIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            // Toggle display
            if (notifDropdown.style.display === 'block') {
                notifDropdown.style.display = 'none';
            } else {
                notifDropdown.style.display = 'block';
                // Appeler la fonction pour marquer comme lu
                markNotificationsAsRead();
            }
        });
        document.addEventListener('click', function() {
            notifDropdown.style.display = 'none';
        });

        // Fonction pour marquer les notifications comme lues
        function markNotificationsAsRead() {
            // Assure-toi que le chemin d'accès est correct
            fetch('/projet_web/web-project2/gestion-module-ue/includes/mark_notifications_as_read.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(data.message);
                        // Optionnel: supprimer le badge immédiatement si réussi
                        var badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.remove(); 
                        }
                    } else {
                        console.error('Erreur:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur réseau ou de traitement:', error);
                });
        }
    }
});
</script>