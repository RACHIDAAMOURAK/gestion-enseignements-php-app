<?php
// Démarrer une session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'db.php'; // Si pas déjà inclus
$pdo = connectDB();

// Récupérer l'ID utilisateur de la session, en vérifiant les deux clés possibles
$userId = $_SESSION['user_id'] ?? $_SESSION['id_utilisateur'] ?? null;

// Vérification des permissions
if (!isset($userId) || ($_SESSION['role'] !== 'enseignant' && $_SESSION['role'] !== 'coordonnateur')) {
    echo "Accès refusé.";
    exit();
}

// Détecter la page actuelle
$current_page = basename($_SERVER['PHP_SELF']);

// Vérifier si nous sommes dans le répertoire gestion-notes
$is_in_gestion_notes = strpos($_SERVER['PHP_SELF'], 'gestion-notes') !== false;

// Déterminer le préfixe de chemin en fonction de l'emplacement actuel
$base_path = $is_in_gestion_notes ? '../' : '';

// Récupérer le nombre de notifications non lues pour l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS count_notifs 
    FROM notifications 
    WHERE id_utilisateur = ? AND statut = 'non_lu'
");
$stmt->execute([$userId]);
$notif_result = $stmt->fetch(PDO::FETCH_ASSOC);
$notification_count = $notif_result['count_notifs'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Département</title>
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
        
        /* Style commun pour tous les boutons d'action */
        .btn-action {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
        }

        .btn-action:hover {
            background-color: var(--accent-color);
            color: var(--text-color);
            text-decoration: none;
        }
        
        .btn-action i {
            font-size: 1rem;
        }
        
        /* Style pour les boutons de suppression/danger */
        .btn-danger {
            background-color: rgba(255, 69, 58, 0.1);
            color: #FF453A;
            border: 1px solid #FF453A;
        }

        .btn-danger:hover {
            background-color: #FF453A;
            color: var(--text-color);
        }
        
        /* Style pour les boutons secondaires */
        .btn-secondary {
            background-color: rgba(112, 134, 171, 0.1);
            color: var(--text-muted);
            border: 1px solid var(--text-muted);
        }
        
        .btn-secondary:hover {
            background-color: var(--text-muted);
            color: var(--text-color);
        }

        /* Style pour les petits boutons */
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Style pour les boutons larges */
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Styles pour les sections communes */
        .section-header {
            margin-bottom: var(--section-margin);
        }
        
        .card {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            margin-bottom: var(--section-margin);
            padding: var(--card-padding);
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            padding: var(--spacing-md);
            font-weight: 500;
            color: var(--text-color);
        }
        
        .card-body {
            padding: var(--spacing-md);
        }

        .form-group {
            margin-bottom: var(--form-spacing);
        }

        .form-control {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }
        
        .form-control:focus {
            background-color: var(--primary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(49, 183, 209, 0.25);
        }

        .table {
            color: var(--text-color);
            border-color: var(--border-color);
        }

        .table th {
            background-color: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 500;
            border-color: var(--border-color);
        }

        .table td {
            border-color: var(--border-color);
        }

        /* Styles pour les notifications */
        .notifications-dropdown {
            position: absolute;
            top: 60px;
            right: 20px;
            width: 350px;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .notifications-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .mark-all-read {
            font-size: 12px;
            color: var(--accent-color);
            cursor: pointer;
            text-decoration: none;
        }
        
        .notification-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background-color: rgba(49, 183, 209, 0.05);
        }
        
        .notification-title {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 500;
        }

        .notification-text {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .notification-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
            display: block;
        }

        .calendar-dropdown {
            position: absolute;
            top: 60px;
            right: 20px;
            width: 300px;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
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
            font-weight: 500;
            color: var(--text-color);
        }
        
        .calendar-grid {
            padding: 15px;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            margin-bottom: 10px;
        }
        
        .calendar-weekday {
            font-size: 12px;
            color: var(--text-muted);
            padding: 5px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .calendar-day {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 30px;
            font-size: 13px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .calendar-day:hover {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
        }
        
        .calendar-day.today {
            background-color: var(--accent-color);
            color: white;
        }
        
        .calendar-day.other-month {
            color: var(--text-muted);
            opacity: 0.5;
        }
        
        .calendar-day.has-event {
            font-weight: bold;
            border: 1px solid var(--accent-color);
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

        .calendar-day.current {
            background-color: var(--accent-color);
            color: white;
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

        /* Ajout styles pour notifications et calendrier (comme ancien header) */
        .nav-icon {
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
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
        .notification-dropdown {
            position: absolute;
            right: 40px;
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
                                </div>
        <div class="menu-items">
            <div class="menu-items-top">
                <a href="<?php echo $base_path; ?>souhaits_enseignants.php" class="menu-item <?= $current_page == 'souhaits_enseignants.php' ? 'active' : '' ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span class="menu-text">Expression des souhaits</span>
                </a>
                <a href="<?php echo $base_path; ?>mes_modules.php" class="menu-item <?= $current_page == 'mes_modules.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i>
                    <span class="menu-text">Mes modules</span>
                </a>
                <a href="<?php echo $base_path; ?>gestion-notes/index.php" class="menu-item <?= $is_in_gestion_notes ? 'active' : '' ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="menu-text">Gestion des notes</span>
                </a>
            </div>
            <div class="menu-items-bottom">
                <a href="<?php echo $base_path; ?>profile.php" class="menu-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">Mon Profil</span>
                </a>
               
                <a href="<?php echo $base_path; ?>../../logout.php" class="menu-item">
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
                 <!-- Icône de notifications -->
                 <div class="nav-icon" id="notificationIcon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="icon-badge" id="notificationBadge"><?= $notification_count ?></span>
                    <?php endif; ?>
                </div>
                <div class="action-button" id="calendar-toggle">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <a href="profile.php" class="user-profile">
                    <div class="user-avatar">
                        <?php 
                        if (isset($_SESSION['user_id']) && isset($_SESSION['prenom'])) {
                            echo strtoupper(substr($_SESSION['nom_utilisateur'], 0, 1) . substr($_SESSION['prenom'], 0, 1));
                        } else {
                            echo substr($_SESSION['role'], 0, 2); 
                        }
                        ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            if (isset($_SESSION['nom_utilisateur']) && isset($_SESSION['prenom'])) {
                                echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom_utilisateur']);
                            } else {
                                echo "Utilisateur";
                            }
                            ?>
                        </span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </a>
                <a href="<?php echo $base_path; ?>../../logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                    </div>
                </div>
    </nav>

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
                        $stmt->execute([$_SESSION['user_id']]);
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
                
        
    <!-- Calendar Dropdown -->
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

    <!-- Main Container -->
    <div class="main-container">
        
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
                const calendarToggle = document.getElementById('calendar-toggle');
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
                calendarToggle.addEventListener('click', function() {
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
                console.log('Marquage de la notification: ' + notifId);
                
                // Appel AJAX pour marquer comme lu
                fetch('mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notif_id=' + notifId
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Réponse du serveur:', data);
                    
                    if (data.success) {
                        // Mettre à jour l'apparence visuelle
                        this.classList.remove('unread');
                        
                        // Mettre à jour le compteur
                        updateNotificationBadge();
                    } else {
                        console.error('Erreur lors du marquage:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
            }
        });
    });

      // Marquer toutes les notifications comme lues
    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            console.log('Marquage de toutes les notifications comme lues');
            
            // Appel AJAX pour tout marquer comme lu
            fetch('mark_all_notifications_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse du serveur:', data);
                
                if (data.success) {
                    console.log(`${data.count} notifications marquées comme lues`);
                    
                    // Mettre à jour l'apparence de toutes les notifications
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Cacher le badge de notification
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                } else {
                    console.error('Erreur lors du marquage de toutes les notifications:', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
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
                            
                                    // Ajouter l'événement clic pour marquer comme lu
                            notifItem.addEventListener('click', function() {
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
                                                    // Recharger les notifications pour mettre à jour le compteur
                                                    loadNotifications();
                                        }
                                    })
                                            .catch(error => console.error('Erreur:', error));
                                        }
                                    });
                                    
                                    container.appendChild(notifItem);
                        });
                        
                                // Afficher le bouton "Tout marquer comme lu"
                                const footer = document.querySelector('.notification-footer');
                                if (!footer) {
                                    const newFooter = document.createElement('div');
                                    newFooter.className = 'notification-footer';
                                    newFooter.innerHTML = '<button id="markAllRead" class="mark-read-btn">Tout marquer comme lu</button>';
                                    notificationDropdown.appendChild(newFooter);
                            
                                    // Ajouter l'événement pour marquer tout comme lu
                                    document.getElementById('markAllRead').addEventListener('click', function() {
                                fetch('mark_all_notifications_read.php', {
                                    method: 'POST'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                                // Recharger les notifications pour mettre à jour l'affichage
                                                loadNotifications();
                                            }
                                        })
                                        .catch(error => console.error('Erreur:', error));
                                    });
                                }
                            } else {
                                container.innerHTML = '<div class="no-notifications">Aucune notification</div>';
                                // Supprimer le footer s'il existe
                                const footer = document.querySelector('.notification-footer');
                                if (footer) {
                                    footer.remove();
                                }
                            }
                            
                            // Mettre à jour le badge
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
                    if (!calendarDropdown.contains(event.target) && !calendarToggle.contains(event.target)) {
                        calendarDropdown.style.display = 'none';
                    }
                });

                // Vérifier les nouvelles notifications périodiquement
                setInterval(loadNotifications, 30000);
        });
        </script>
</body>
</html>



