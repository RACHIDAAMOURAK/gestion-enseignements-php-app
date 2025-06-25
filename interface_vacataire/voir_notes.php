<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un vacataire
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vacataire') {
    header("Location: ../login.php");
    exit();
}

require_once 'config/database.php';
require_once 'vendor/autoload.php';

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
        sp.nom as specialite_nom
    FROM utilisateurs u
    LEFT JOIN departements d ON u.id_departement = d.id
    LEFT JOIN utilisateur_specialites us ON u.id = us.id_utilisateur
    LEFT JOIN specialites sp ON us.id_specialite = sp.id
    WHERE u.id = ? AND u.role = 'vacataire' AND u.actif = 1
");
$stmt->execute([$vacataire_id]);
$vacataire = $stmt->fetch();

if (!isset($_GET['fichier'])) {
    die('Fichier non spécifié.');
}

$fichier_id = intval($_GET['fichier']);

// Récupérer le chemin du fichier
$stmt = $pdo->prepare("SELECT chemin_fichier, nom_fichier FROM fichiers_notes WHERE id = ?");
$stmt->execute([$fichier_id]);
$fichier = $stmt->fetch();

if (!$fichier) {
    die('Fichier introuvable.');
}

$file_path = '../uploads/notes/' . $fichier['chemin_fichier'];
if (!file_exists($file_path)) {
    die('Le fichier n\'existe plus sur le serveur.');
}

// Lire le fichier Excel
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
$worksheet = $spreadsheet->getActiveSheet();
$data = $worksheet->toArray();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation des notes - Vacataire</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--primary-bg); color: var(--text-color); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-width-collapsed); background-color: var(--primary-bg); border-right: 1px solid var(--border-color); transition: width 0.3s ease; z-index: 1000; overflow: hidden; }
        body.menu-expanded .sidebar { width: var(--sidebar-width-expanded); }
        .sidebar-logo { padding: 1.5rem 0; color: var(--accent-color); text-align: center; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; }
        .menu-items { display: flex; flex-direction: column; height: calc(100% - 60px); padding: 1rem 0; justify-content: space-between; }
        .menu-items-top { display: flex; flex-direction: column; gap: 1rem; }
        .menu-items-bottom { margin-top: auto; display: flex; flex-direction: column; gap: 1rem; }
        .menu-item { color: var(--text-muted); text-decoration: none; padding: 0.75rem 1rem; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem; white-space: nowrap; cursor: pointer; }
        .menu-item i { font-size: 1.1rem; width: 20px; text-align: center; transition: color 0.2s ease; display: flex; align-items: center; justify-content: center; color: #8A9CC9; }
        .menu-toggle { color: var(--accent-color); text-align: center; padding: 1.2rem 0; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; margin-top: 0.5rem; width: 100%; }
        .menu-toggle i { font-size: 1.5rem; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .menu-toggle:hover i { transform: rotate(180deg); }
        body.menu-expanded .menu-toggle i { transform: rotate(-180deg); }
        body.menu-expanded .menu-toggle:hover i { transform: rotate(-360deg); }
        .menu-toggle .menu-text { display: none; }
        body.menu-expanded .menu-toggle { justify-content: flex-start; padding-left: 1.5rem; }
        .menu-item:hover, .menu-item.active { color: var(--text-color); background-color: rgba(49, 183, 209, 0.1); }
        .menu-item.active { background-color: var(--secondary-bg); border-left: 3px solid var(--accent-color); }
        .menu-item:hover i, .menu-item.active i { color: var(--accent-color) !important; }
        .menu-text { opacity: 0; visibility: hidden; transition: opacity 0.2s, visibility 0.2s; font-size: 0.95rem; }
        body.menu-expanded .menu-text { opacity: 1; visibility: visible; }
        .top-nav { position: fixed; top: 0; right: 0; left: var(--sidebar-width-collapsed); z-index: 999; height: var(--top-nav-height); background-color: var(--primary-bg); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; padding: 0 var(--spacing-md); transition: left 0.3s ease; }
        body.menu-expanded .top-nav { left: var(--sidebar-width-expanded); }
        .container-fluid { width: 100%; }
        .user-menu { display: flex; align-items: center; gap: 1.5rem; justify-content: flex-end; width: 100%; }
        .user-profile { display: flex; align-items: center; gap: 0.75rem; color: var(--text-color); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 0.375rem; transition: all 0.2s; }
        .user-profile:hover { background-color: rgba(49, 183, 209, 0.1); }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 500; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { color: var(--text-color); font-size: 0.875rem; font-weight: 500; }
        .user-role { color: #8A9CC9; font-size: 0.75rem; }
        .action-button { color: #8A9CC9; font-size: 1.1rem; position: relative; cursor: pointer; padding: 0.5rem; border-radius: 0.375rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
        .action-button:hover { color: var(--accent-color); background-color: rgba(49, 183, 209, 0.1); }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: var(--accent-color); color: white; font-size: 0.7rem; min-width: 16px; height: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }
        .logout-button { color: #8A9CC9; background: transparent; border: none; padding: 0.5rem; border-radius: 0.375rem; transition: all 0.2s; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
        .logout-button:hover { color: var(--accent-color); background-color: rgba(74, 71, 71, 0.14); }
        .main-container { margin-left: var(--sidebar-width-collapsed); padding-top: var(--top-nav-height); padding-bottom: var(--footer-height); flex: 1; min-height: 100vh; transition: margin-left 0.3s ease; overflow-y: auto; }
        body.menu-expanded .main-container { margin-left: var(--sidebar-width-expanded); }
        .content { padding: var(--content-padding); min-height: calc(100vh - var(--top-nav-height) - var(--footer-height)); }
        .footer { background-color: var(--primary-bg); border-top: 1px solid var(--border-color); padding: 1rem; position: fixed; bottom: 0; right: 0; left: var(--sidebar-width-collapsed); z-index: 999; transition: left 0.3s ease; height: var(--footer-height); display: flex; align-items: center; justify-content: center; }
        body.menu-expanded .footer { left: var(--sidebar-width-expanded); }
        .footer p { color: #8A9CC9; font-size: 0.875rem; margin: 0; }
        .footer span { color: var(--text-color); margin: 0 0.5rem; }
        .card { background-color: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 0.75rem; margin-bottom: var(--section-margin); box-shadow: 0 4px 24px rgba(49,183,209,0.08); }
        .card-header { background-color: rgba(49, 183, 209, 0.08); border-bottom: 1px solid var(--border-color); padding: 1.25rem; color: var(--accent-color); font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 0.75rem; letter-spacing: 0.5px; }
        .card-header i { color: var(--accent-color); font-size: 1.3rem; margin-right: 0.5rem; }
        .card-body { padding: var(--card-padding); }
        .btn-secondary { background: #6c757d; border: none; color: #fff; border-radius: 0.5rem; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: background 0.2s, box-shadow 0.2s; }
        .btn-secondary:hover { background: #495057; box-shadow: 0 4px 16px rgba(49,183,209,0.10); }
        .table { background-color: transparent; color: var(--text-color); margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .table th { background-color: rgba(49, 183, 209, 0.13); border-color: var(--border-color); color: var(--text-color); font-weight: 700; padding: 1rem; font-size: 1.05rem; letter-spacing: 0.5px; }
        .table td { border-color: var(--border-color); color: var(--text-color); padding: 1rem; vertical-align: middle; font-size: 1.01rem; }
        .table tbody tr { transition: background-color 0.2s; }
        .table tbody tr:hover { background-color: rgba(49, 183, 209, 0.07); }
        .badge { padding: 0.6em 1.1em; border-radius: 1.2em; font-size: 1em; font-weight: 600; letter-spacing: 0.03em; }
        .bg-success { background-color: #28a745 !important; color: #fff !important; }
        .bg-danger { background-color: #dc3545 !important; color: #fff !important; }
        .bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .bg-secondary { background-color: #6c757d !important; color: #fff !important; }
        @media (max-width: 768px) {
            .main-container { padding-left: 0; padding-right: 0; }
            .card { border-radius: 0.5rem; }
            .table th, .table td { padding: 0.5rem; font-size: 0.95rem; }
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
                <a href="dashboard.php#modules-section" class="menu-item" data-section="modules">
                    <i class="fas fa-book"></i>
                    <span class="menu-text">Mes Modules</span>
                </a>
                <a href="gestion_notes.php" class="menu-item" data-section="notes">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">Gestion des Notes</span>
                </a>
                <a href="dashboard.php#historique-section" class="menu-item" data-section="historique">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Historique</span>
                </a>
            </div>
            <div class="menu-items-bottom">
                <a href="dashboard.php#profile-section" class="menu-item" data-section="profile">
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
                <a href="logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8">
                        <div class="card mt-4">
                            <div class="card-header">
                                <i class="fas fa-eye me-2"></i>
                                Visualisation du fichier : <?php echo htmlspecialchars($fichier['nom_fichier']); ?>
                            </div>
                            <div class="card-body">
                                <a href="gestion_notes.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Retour</a>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <?php foreach ($data[0] as $header): ?>
                                                <th><?php echo htmlspecialchars($header); ?></th>
                                            <?php endforeach; ?>
                                            <th>Statut</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php for ($i = 1; $i < count($data); $i++): ?>
                                            <tr>
                                                <?php foreach ($data[$i] as $cell): ?>
                                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <?php
                                                    $note = isset($data[$i][1]) ? str_replace(',', '.', $data[$i][1]) : '';
                                                    if ($note === '' || strtoupper($note) === 'ABS') {
                                                        echo '<span class="badge bg-warning">Absent</span>';
                                                    } elseif (is_numeric($note)) {
                                                        if ($note >= 10) {
                                                            echo '<span class="badge bg-success">Validée</span>';
                                                        } else {
                                                            echo '<span class="badge bg-danger">Rattrapage</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Inconnu</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
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
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('menu-expanded');
                    localStorage.setItem('menuExpanded', document.body.classList.contains('menu-expanded'));
                });
            }
            // Load saved menu state
            const savedMenuState = localStorage.getItem('menuExpanded');
            if (savedMenuState === 'true') {
                document.body.classList.add('menu-expanded');
            }
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
        });
    </script>
</body>
</html> 