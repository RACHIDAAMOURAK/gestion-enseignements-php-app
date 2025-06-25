<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Récupérer le nombre de notifications non lues pour l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS count_notifs 
    FROM notifications 
    WHERE id_utilisateur = ? AND statut = 'non_lu'
");
$stmt->execute([$_SESSION['id_utilisateur']]);
$notif_result = $stmt->fetch(PDO::FETCH_ASSOC);
$notification_count = $notif_result['count_notifs'];


// Récupérer le nom complet de l'utilisateur depuis la session
$user_fullname = isset($_SESSION['nom_complet']) ? $_SESSION['nom_complet'] : 'Utilisateur';
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
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-icon {
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .nav-icon i {
            font-size: 20px;
            color: var(--text-muted);
        }
        
        .nav-icon:hover i {
            color: var(--accent-color);
        }
        
        .icon-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
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
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: rgba(49, 183, 209, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--accent-color);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(49, 183, 209, 0.2);
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

        /* This will be used both in header and in profile page */
        .badge-role {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: rgba(49, 183, 209, 0.15);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .badge-role i {
            margin-right: 0.4rem;
            font-size: 0.8rem;
        }
        
        /* Profile header styling for consistency */
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h3 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
    color: var(--accent-color);
            font-weight: 600;
        }
        
        .profile-info p {
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
}
       /* Styles pour le calendrier */
       .calendar-dropdown {
            position: absolute;
            right: 100px;
            top: 60px;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 300px;
            border-radius: 8px;
            overflow: hidden;
            z-index: 1000;
            display: none;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: var(--primary-bg);
            border-bottom: 1px solid var(--border-color);
        }
        
        .calendar-nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .calendar-nav i {
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        
        .calendar-nav i:hover {
            color: var(--accent-color);
        }
        
        .calendar-title {
            font-weight: bold;
            color: var(--text-color);
        }
        
        .calendar-grid {
            padding: 10px;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .weekday {
            color: var(--text-muted);
            font-weight: bold;
            font-size: 12px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-top: 8px;
        }
        
        .day {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .day:hover {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
        }
        
        .day.empty {
            cursor: default;
        }
        
        .day.current {
            background-color: rgba(49, 183, 209, 0.2);
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .calendar-footer {
            padding: 10px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        
        .today-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .today-btn:hover {
            background-color: #2aa3b9;
        }

        /* Styles pour les notifications */
        .notification-dropdown {
            position: absolute;
            right: 150px;
            top: 60px;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 350px;
            border-radius: 8px;
            overflow: hidden;
            z-index: 1000;
            display: none;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background-color: var(--primary-bg);
            border-bottom: 1px solid var(--border-color);
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            color: var(--text-color);
        }
        
        .notification-header i {
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s;
        }
        
        .notification-header i:hover {
            color: var(--accent-color);
        }
        
        .notification-container {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .notification-item {
            display: flex;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background-color: rgba(49, 183, 209, 0.05);
        }
        
        .notification-item.unread {
            background-color: rgba(49, 183, 209, 0.1);
        }
        
        .notif-icon {
            margin-right: 12px;
            display: flex;
            align-items: center;
            font-size: 18px;
        }
        
        .notif-content {
            flex: 1;
        }
        
        .notif-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .notif-message {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .notif-time {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .no-notifications {
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .notification-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }
        
        .mark-read-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .mark-read-btn:hover {
            background-color: #2aa3b9;
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
                <a href="lister_professeurs_departement.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'lister_professeurs_departement.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span class="menu-text">Enseignants du département</span>
                </a>
                
                <a href="validation_souhaits.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'validation_souhaits.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span class="menu-text">Validation des vœux</span>
                </a>
                
                <a href="affectation_enseignants_validations_unites_vacantes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'affectation_enseignants_validations_unites_vacantes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span class="menu-text">Affectation des enseignants</span>
                </a>
                
                <a href="generations_rapports_decisions.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'generations_rapports_decisions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="menu-text">Rapports et historique</span>
                </a>
                    </div>

            <div class="menu-items-bottom">
                <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">Mon profil</span>
                </a>
                <a href="logout.php" class="menu-item">
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
                <!-- Icône de calendrier -->
                <div class="nav-icon" id="calendarIcon" title="Calendrier">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                
                <!-- Icône de notifications -->
                <div class="nav-icon" id="notificationIcon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="icon-badge" id="notificationBadge"><?= $notification_count ?></span>
                    <?php endif; ?>
                </div>
                
                 <!-- Dropdown de notifications -->
                 <div id="notificationDropdown" class="notification-dropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <i class="fas fa-times" id="closeNotifications"></i>
                    </div>
                    <div class="notification-container" id="notificationContainer">
                        <?php
                        // Récupérer les notifications de l'utilisateur
                        $stmt = $pdo->prepare("
                            SELECT id, titre, message, type, date_creation, statut
                            FROM notifications
                            WHERE id_utilisateur = ?
                            ORDER BY date_creation DESC
                            LIMIT 10
                        ");
                        $stmt->execute([$_SESSION['id_utilisateur']]);
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($notifications) > 0) {
                            foreach ($notifications as $notif):
                                $notif_class = $notif['statut'] === 'non_lu' ? 'unread' : '';
                                $icon_class = '';
                                switch ($notif['type']) {
                                    case 'warning': $icon_class = 'fa-exclamation-triangle text-warning'; break;
                                    case 'info': $icon_class = 'fa-info-circle text-info'; break;
                                    case 'success': $icon_class = 'fa-check-circle text-success'; break;
                                    default: $icon_class = 'fa-bell text-muted';
                                }
                        ?>
                            <div class="notification-item <?= $notif_class ?>" data-notif-id="<?= $notif['id'] ?>">
                                <div class="notif-icon">
                                    <i class="fas <?= $icon_class ?>"></i>
                                </div>
                                <div class="notif-content">
                                    <div class="notif-title"><?= htmlspecialchars($notif['titre']) ?></div>
                                    <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                    <div class="notif-time"><?= date('d/m/Y H:i', strtotime($notif['date_creation'])) ?></div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        } else {
                            echo '<div class="no-notifications">Aucune notification</div>';
                        }
                        ?>
                    </div>
                    <?php if (count($notifications) > 0): ?>
                        <div class="notification-footer">
                            <button id="markAllRead" class="mark-read-btn">Tout marquer comme lu</button>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="profile.php" class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
            </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo $user_fullname; ?></span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                </div>
                </a>
                <a href="logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
     <!-- Calendrier déroulant -->
     <div id="calendarDropdown" class="calendar-dropdown">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <i class="fas fa-angle-left" id="prevMonth"></i>
                    <div class="calendar-title" id="calendarTitle">Avril 2025</div>
                    <i class="fas fa-angle-right" id="nextMonth"></i>
                </div>
                <i class="fas fa-times" id="closeCalendar"></i>
            </div>
            <div class="calendar-grid">
                <div class="calendar-weekdays">
                    <div class="weekday">Lu</div>
                    <div class="weekday">Ma</div>
                    <div class="weekday">Me</div>
                    <div class="weekday">Je</div>
                    <div class="weekday">Ve</div>
                    <div class="weekday">Sa</div>
                    <div class="weekday">Di</div>
                </div>
                <div class="calendar-days" id="calendarDays">
                    <!-- Les jours seront générés par JavaScript -->
                </div>
            </div>
            <div class="calendar-footer">
                <button class="today-btn" id="todayBtn">Aujourd'hui</button>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Éléments pour les notifications
                const notificationIcon = document.getElementById('notificationIcon');
                const notificationDropdown = document.getElementById('notificationDropdown');
                const closeNotifications = document.getElementById('closeNotifications');
                const notificationContainer = document.getElementById('notificationContainer');
                const notificationItems = document.querySelectorAll('.notification-item');
                const markAllRead = document.getElementById('markAllRead');
                
                // Éléments pour le calendrier
                const calendarIcon = document.getElementById('calendarIcon');
                const calendarDropdown = document.getElementById('calendarDropdown');
                const closeCalendar = document.getElementById('closeCalendar');
                const prevMonth = document.getElementById('prevMonth');
                const nextMonth = document.getElementById('nextMonth');
                const calendarTitle = document.getElementById('calendarTitle');
                const calendarDays = document.getElementById('calendarDays');
                const todayBtn = document.getElementById('todayBtn');
                
                // Variables pour le calendrier
                let currentDate = new Date();
                
                // Afficher/masquer le dropdown des notifications
                notificationIcon.addEventListener('click', function() {
                    if (notificationDropdown.style.display === 'block') {
                        notificationDropdown.style.display = 'none';
                    } else {
                        notificationDropdown.style.display = 'block';
                        calendarDropdown.style.display = 'none';
                        loadNotifications();
                    }
                });

                // Fermer le dropdown des notifications
                closeNotifications.addEventListener('click', function() {
                    notificationDropdown.style.display = 'none';
                });

                // Afficher/masquer le dropdown du calendrier
                calendarIcon.addEventListener('click', function() {
                    if (calendarDropdown.style.display === 'block') {
                        calendarDropdown.style.display = 'none';
                    } else {
                        calendarDropdown.style.display = 'block';
                        notificationDropdown.style.display = 'none';
                        generateCalendar(currentDate);
                    }
                });
                
                // Fermer le calendrier
                closeCalendar.addEventListener('click', function() {
                    calendarDropdown.style.display = 'none';
                });
                
                // Navigation mois précédent
                prevMonth.addEventListener('click', function() {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    generateCalendar(currentDate);
                });
                
                // Navigation mois suivant
                nextMonth.addEventListener('click', function() {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    generateCalendar(currentDate);
                });
                
                // Bouton aujourd'hui
                todayBtn.addEventListener('click', function() {
                    currentDate = new Date();
                    generateCalendar(currentDate);
                });
                
                // Fonction pour générer le calendrier
                function generateCalendar(date) {
                    const year = date.getFullYear();
                    const month = date.getMonth();
                    
                    // Mettre à jour le titre
                    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                    calendarTitle.textContent = `${monthNames[month]} ${year}`;
                    
                    // Vider le conteneur des jours
                    calendarDays.innerHTML = '';
                    
                    // Obtenir le premier jour du mois (0 = Dimanche, 1 = Lundi, ...)
                    const firstDayOfMonth = new Date(year, month, 1).getDay();
                    // Convertir pour que la semaine commence le lundi (0 = Lundi, 6 = Dimanche)
                    const firstDayIndex = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
                    
                    // Obtenir le nombre de jours dans le mois
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    
                    // Ajouter les cases vides pour le début du mois
                    for (let i = 0; i < firstDayIndex; i++) {
                        const emptyDay = document.createElement('div');
                        emptyDay.className = 'day empty';
                        calendarDays.appendChild(emptyDay);
                    }
                    
                    // Ajouter les jours du mois
                    const today = new Date();
                    for (let i = 1; i <= daysInMonth; i++) {
                        const dayElement = document.createElement('div');
                        dayElement.className = 'day';
                        dayElement.textContent = i;
                        
                        // Vérifier si c'est aujourd'hui
                        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
                            dayElement.classList.add('current');
                        }
                        
                        calendarDays.appendChild(dayElement);
                    }
                }

                // Marquer une notification comme lue au clic
                notificationItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const notifId = this.getAttribute('data-notif-id');
                        if (notifId) {
                            fetch('mark_notification_read.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'notif_id=' + notifId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    this.classList.remove('unread');
                                    updateNotificationBadge();
                                }
                            })
                            .catch(error => console.error('Erreur:', error));
                        }
                    });
                });

                // Marquer toutes les notifications comme lues
                if (markAllRead) {
                    markAllRead.addEventListener('click', function() {
                        fetch('mark_all_notifications_read.php', {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.querySelectorAll('.notification-item').forEach(item => {
                                    item.classList.remove('unread');
                                });
                                const badge = document.getElementById('notificationBadge');
                                if (badge) {
                                    badge.style.display = 'none';
                                }
                            }
                        })
                        .catch(error => console.error('Erreur:', error));
                    });
                }
                
                // Charger les notifications
                function loadNotifications() {
                    fetch('check_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('notificationContainer');
                            container.innerHTML = '';
                            
                            if (data.notifications && data.notifications.length > 0) {
                                data.notifications.forEach(notif => {
                                    const notifItem = document.createElement('div');
                                    notifItem.className = `notification-item ${notif.statut === 'non_lu' ? 'unread' : ''}`;
                                    notifItem.setAttribute('data-notif-id', notif.id);
                                    
                                    let iconClass = 'fa-bell text-muted';
                                    switch (notif.type) {
                                        case 'warning': iconClass = 'fa-exclamation-triangle text-warning'; break;
                                        case 'info': iconClass = 'fa-info-circle text-info'; break;
                                        case 'success': iconClass = 'fa-check-circle text-success'; break;
                                    }
                                    
                                    notifItem.innerHTML = `
                                        <div class="notif-icon">
                                            <i class="fas ${iconClass}"></i>
                                        </div>
                                        <div class="notif-content">
                                            <div class="notif-title">${notif.titre}</div>
                                            <div class="notif-message">${notif.message}</div>
                                            <div class="notif-time">${notif.date_creation}</div>
                                        </div>
                                    `;
                                    
                                    container.appendChild(notifItem);
                                });
                                
                                const footer = document.querySelector('.notification-footer');
                                if (!footer) {
                                    const newFooter = document.createElement('div');
                                    newFooter.className = 'notification-footer';
                                    newFooter.innerHTML = '<button id="markAllRead" class="mark-read-btn">Tout marquer comme lu</button>';
                                    notificationDropdown.appendChild(newFooter);
                                }
                            } else {
                                container.innerHTML = '<div class="no-notifications">Aucune notification</div>';
                                const footer = document.querySelector('.notification-footer');
                                if (footer) {
                                    footer.remove();
                                }
                            }
                            
                            updateNotificationBadge(data.count || 0);
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
                }
                
                // Fonction pour mettre à jour le badge de notification
                function updateNotificationBadge(count) {
                    const badge = document.getElementById('notificationBadge');
                    if (count > 0) {
                        if (badge) {
                            badge.textContent = count;
                            badge.style.display = 'flex';
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.id = 'notificationBadge';
                            newBadge.className = 'icon-badge';
                            newBadge.textContent = count;
                            notificationIcon.appendChild(newBadge);
                        }
                    } else {
                        if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                }

                // Fermer les dropdowns si on clique en dehors
                document.addEventListener('click', function(event) {
                    if (!notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
                        notificationDropdown.style.display = 'none';
                    }
                    if (!calendarDropdown.contains(event.target) && !calendarIcon.contains(event.target)) {
                        calendarDropdown.style.display = 'none';
                    }
                });

                // Vérifier les nouvelles notifications périodiquement
                setInterval(loadNotifications, 30000);
            });
        </script>
    
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
    // Gestion du menu
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
        localStorage.setItem('menuExpanded', isExpanded);
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