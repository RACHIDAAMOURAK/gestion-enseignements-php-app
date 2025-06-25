<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un vacataire
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vacataire') {
    // Rediriger vers la page de connexion
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/config/database.php';

// Récupération des informations du vacataire
$vacataire_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.nom_utilisateur,
        u.email,
        u.prenom,
        u.nom,
        u.role,
        u.specialite,
        u.id_departement,
        d.nom as departement_nom,
        sp.nom as specialite_nom,
        u.date_creation,
        u.derniere_connexion
    FROM utilisateurs u
    LEFT JOIN departements d ON u.id_departement = d.id
    LEFT JOIN utilisateur_specialites us ON u.id = us.id_utilisateur
    LEFT JOIN specialites sp ON us.id_specialite = sp.id
    WHERE u.id = ? AND u.role = 'vacataire' AND u.actif = 1
");
$stmt->execute([$vacataire_id]);
$vacataire = $stmt->fetch();

if (!$vacataire) {
    $_SESSION['error_message'] = "Accès non autorisé ou compte inactif.";
    header('Location: ../login.php');
    exit();
}

// Récupérer les années disponibles à partir des dates d'affectation
$stmt = $pdo->prepare("
    SELECT DISTINCT YEAR(date_affectation) as annee 
    FROM historique_affectations_vacataire 
    WHERE id_vacataire = ? 
    ORDER BY annee DESC
");
$stmt->execute([$vacataire_id]);
$annees = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les modules disponibles pour le filtre
$stmt = $pdo->prepare("
    SELECT DISTINCT ue.id, ue.code, ue.intitule
    FROM historique_affectations_vacataire h
    JOIN unites_enseignement ue ON h.id_unite_enseignement = ue.id
    WHERE h.id_vacataire = ?
    ORDER BY ue.code
");
$stmt->execute([$vacataire_id]);
$modules_disponibles = $stmt->fetchAll();

// Traitement des filtres
$annee_selectionnee = $_GET['annee'] ?? '';
$semestre_selectionne = $_GET['semestre'] ?? '';
$module_selectionne = $_GET['module'] ?? '';

// Construction de la requête pour l'historique
$query = "
    SELECT 
        h.*,
        ue.id as module_id,
        ue.code,
        ue.intitule,
        ue.volume_horaire_cm,
        ue.volume_horaire_td,
        ue.volume_horaire_tp,
        ue.semestre,
        f.nom as filiere_nom,
        d.nom as departement_nom,
        h.date_affectation,
        h.type_cours,
        YEAR(h.date_affectation) as annee
    FROM historique_affectations_vacataire h
    JOIN unites_enseignement ue ON h.id_unite_enseignement = ue.id
    LEFT JOIN filieres f ON ue.id_filiere = f.id
    LEFT JOIN departements d ON ue.id_departement = d.id
    WHERE h.id_vacataire = ?
    AND h.action = 'affectation'
";

$params = [$vacataire_id];

if ($annee_selectionnee) {
    $query .= " AND YEAR(h.date_affectation) = ?";
    $params[] = $annee_selectionnee;
}

if ($semestre_selectionne) {
    $query .= " AND ue.semestre = ?";
    $params[] = $semestre_selectionne;
}

if ($module_selectionne) {
    $query .= " AND ue.id = ?";
    $params[] = $module_selectionne;
}

$query .= " ORDER BY h.date_affectation DESC, ue.semestre";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$modules = $stmt->fetchAll();

// Grouper les modules par année
$modules_par_annee = [];
foreach ($modules as $module) {
    $annee = $module['annee'];
    if (!isset($modules_par_annee[$annee])) {
        $modules_par_annee[$annee] = [];
    }
    $modules_par_annee[$annee][] = $module;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - Espace Vacataire</title>
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
            cursor: pointer;
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
            background-color: rgba(74, 71, 71, 0.14);
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

        .card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: var(--section-margin);
            overflow: hidden;
        }

        .card-header {
            background-color: rgba(49, 183, 209, 0.1);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .card-body {
            padding: var(--card-padding);
        }

        .table {
            background-color: transparent;
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table th {
            background-color: rgba(49, 183, 209, 0.1);
            border-color: var(--border-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 0.75rem;
        }

        .table td {
            border-color: var(--border-color);
            color: var(--text-color);
            padding: 0.75rem;
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a9fb5;
            border-color: #2a9fb5;
        }

        .form-control {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--secondary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.2rem rgba(49, 183, 209, 0.25);
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

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
                <a href="dashboard.php" class="menu-item" data-section="dashboard">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Tableau de bord</span>
                </a>
                
                <a href="gestion_notes.php" class="menu-item" data-section="notes">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">Gestion des Notes</span>
                </a>
                
                <a href="historique.php" class="menu-item active" data-section="historique">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Historique</span>
                </a>
            </div>

            <div class="menu-items-bottom">
                <a href="profil.php" class="menu-item" data-section="profile" onclick="window.location.href='profil.php'; return false;">
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
                <a href="profil.php" class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($vacataire['prenom'], 0, 1) . substr($vacataire['nom'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($vacataire['prenom'] . ' ' . $vacataire['nom']); ?></span>
                        <span class="user-role"><?php echo ucfirst($vacataire['role']); ?></span>
                    </div>
                </a>
                <a href="#" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="text-white mb-3">
                            <i class="fas fa-history me-2" style="color: var(--accent-color);"></i>
                            Historique des Années Passées
                        </h2>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Année</label>
                        <select class="form-control" name="annee" id="annee">
                            <option value="">Toutes les années</option>
                            <?php foreach ($annees as $annee): ?>
                            <option value="<?php echo htmlspecialchars($annee); ?>" <?php echo $annee_selectionnee == $annee ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($annee); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Semestre</label>
                        <select class="form-control" name="semestre" id="semestre">
                            <option value="">Tous les semestres</option>
                            <option value="S1" <?php echo $semestre_selectionne === 'S1' ? 'selected' : ''; ?>>Semestre 1</option>
                            <option value="S2" <?php echo $semestre_selectionne === 'S2' ? 'selected' : ''; ?>>Semestre 2</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Module</label>
                        <select class="form-control" name="module" id="module">
                            <option value="">Tous les modules</option>
                            <?php foreach ($modules_disponibles as $module): ?>
                            <option value="<?php echo htmlspecialchars($module['id']); ?>" <?php echo $module_selectionne == $module['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['code'] . ' - ' . $module['intitule']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary" onclick="appliquerFiltres()">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                </div>

                <!-- Historique par année -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Historique des Affectations
                            </div>
                            <div class="card-body">
                                <?php if (empty($modules_par_annee)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune affectation trouvée pour les critères sélectionnés.
                                </div>
                                <?php else: ?>
                                    <?php foreach ($modules_par_annee as $annee => $modules_annee): ?>
                                    <div class="mb-4">
                                        <h5 class="text-info mb-3">
                                            <i class="fas fa-calendar me-2"></i>
                                            Année <?php echo htmlspecialchars($annee); ?>
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Semestre</th>
                                                        <th>Module</th>
                                                        <th>Type</th>
                                                        <th>Volume Horaire</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($modules_annee as $module): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($module['semestre'] ?? ''); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($module['code'] . ' - ' . $module['intitule']); ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($module['filiere_nom'] ?? ''); ?></small>
                                                        </td>
                                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($module['type_cours'] ?? ''); ?></span></td>
                                                        <td>
                                                            <?php
                                                            $total_heures = ($module['volume_horaire_cm'] ?? 0) + 
                                                                          ($module['volume_horaire_td'] ?? 0) + 
                                                                          ($module['volume_horaire_tp'] ?? 0);
                                                            echo $total_heures . 'h';
                                                            ?>
                                                        </td>
                                                        <td><span class="badge badge-success">Terminé</span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>
                &copy; 2025 Système de Gestion des Affectations - ENSA Al Hoceima
                <span>|</span>
                <span style="color: #8A9CC9;">Version 1.0.0</span>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle functionality
            const menuToggle = document.getElementById('menu-toggle');
            
            if (!menuToggle) {
                console.error("Élément menu-toggle non trouvé!");
                return;
            }
            
            // Load saved menu state
            const savedMenuState = localStorage.getItem('menuExpanded');
            if (savedMenuState === 'true') {
                document.body.classList.add('menu-expanded');
            }
            
            // Toggle menu function
            function toggleMenu(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                const isExpanded = document.body.classList.toggle('menu-expanded');
                localStorage.setItem('menuExpanded', isExpanded);
            }
            
            // Close menu function
            function closeMenu() {
                document.body.classList.remove('menu-expanded');
                localStorage.setItem('menuExpanded', false);
            }
            
            // Menu toggle event
            menuToggle.addEventListener('click', toggleMenu);
            
            // Prevent menu close when clicking on sidebar
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (document.body.classList.contains('menu-expanded') && 
                    sidebar && !sidebar.contains(e.target) && 
                    e.target !== menuToggle) {
                    closeMenu();
                }
            });

            // Logout functionality
            const logoutButton = document.querySelector('.logout-button');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                        window.location.href = 'logout.php';
                    }
                });
            }

            // Notification bell functionality
            const notificationBell = document.querySelector('.action-button');
            if (notificationBell) {
                notificationBell.addEventListener('click', function() {
                    alert('Vous avez 2 nouvelles notifications:\n1. Notes de MAT105 en attente de validation\n2. Nouveau module affecté: INF401');
                });
            }
        });

        // Fonction pour appliquer les filtres
        function appliquerFiltres() {
            const annee = document.getElementById('annee').value;
            const semestre = document.getElementById('semestre').value;
            const module = document.getElementById('module').value;
            
            let url = 'historique.php?';
            const params = [];
            
            if (annee) params.push('annee=' + encodeURIComponent(annee));
            if (semestre) params.push('semestre=' + encodeURIComponent(semestre));
            if (module) params.push('module=' + encodeURIComponent(module));
            
            window.location.href = url + params.join('&');
        }
    </script>
</body>
</html> 