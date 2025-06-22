<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires pour le traitement des données
include_once '../config/database.php';
include_once '../classes/UserManager.php';
include_once '../classes/SessionManager.php';

// Instancier la base de données et le gestionnaire d'utilisateurs
$database = new Database();
$db = $database->getConnection();
$userManager = new UserManager($db);
$sessionManager = new SessionManager();

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Génération automatique des identifiants
                $prenom = strtolower(trim($_POST['prenom']));
                $nom = strtoupper(trim($_POST['nom']));
                $username = $nom;
                $email = strtolower($prenom . '.' . strtolower($nom)) . '@etu.uae.ac.ma';
                
                // Vérifier si une filière est requise pour le rôle coordonnateur
                if ($_POST['role'] === 'coordonnateur' && empty($_POST['filiere_id'])) {
                    $_SESSION['message'] = "Erreur : Une filière est requise pour le rôle coordonnateur.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                
                // Vérifier si un département est requis pour le rôle chef de département
                if ($_POST['role'] === 'chef_departement' && empty($_POST['department_id'])) {
                    $_SESSION['message'] = "Erreur : Un département est requis pour le rôle chef de département.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                
                // Vérifier si une spécialité est requise pour le rôle enseignant ou vacataire
                if (($_POST['role'] === 'enseignant' || $_POST['role'] === 'vacataire') && empty($_POST['specialite_id'])) {
                    $_SESSION['message'] = "Erreur : Une spécialité est requise pour le rôle " . $_POST['role'] . ".";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                
                // Générer un mot de passe fort
                function generatePassword($length = 10) {
                    $upper = chr(rand(65,90));
                    $lower = chr(rand(97,122));
                    $digit = chr(rand(48,57));
                    $special = substr('!@#$%^&*', rand(0,7), 1);
                    $other = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, $length-4);
                    return str_shuffle($upper . $lower . $digit . $special . $other);
                }
                $password = generatePassword();
                // Préparer les données pour addUser
                $data = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'role' => $_POST['role'],
                    'department_id' => $_POST['department_id'],
                    'prenom' => ucfirst($prenom),
                    'nom' => ucfirst(strtolower($nom)),
                    'filiere_id' => isset($_POST['filiere_id']) ? $_POST['filiere_id'] : null,
                    'specialite_id' => isset($_POST['specialite_id']) ? $_POST['specialite_id'] : null
                ];
                $result = $userManager->addUser($data);
                if ($result['success']) {
                    $_SESSION['message'] = "Utilisateur ajouté avec succès. <br>Nom d'utilisateur : $username<br>Email : $email<br>Mot de passe : $password";
                } else {
                    $_SESSION['message'] = "Erreur : " . $result['message'];
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            case 'edit':
                // Vérifier si une filière est requise pour le rôle coordonnateur
                if ($_POST['role'] === 'coordonnateur' && empty($_POST['filiere_id'])) {
                    $_SESSION['message'] = "Erreur : Une filière est requise pour le rôle coordonnateur.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                $result = $userManager->updateUser($_POST);
                $message = $result['success'] ? "Utilisateur modifié avec succès." : "Erreur : " . $result['message'];
                break;
            case 'delete':
                $result = $userManager->deleteUser($_POST['user_id']);
                $message = $result['success'] ? "Utilisateur supprimé avec succès." : "Erreur : " . $result['message'];
                break;
            case 'toggle_status':
                $result = $userManager->toggleUserStatus($_POST['user_id']);
                $message = $result['success'] ? "Statut de l'utilisateur modifié avec succès." : "Erreur : " . $result['message'];
                break;
        }
    }
}

// Récupérer les paramètres de pagination et de filtrage
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Récupérer le nombre total d'utilisateurs et calculer le nombre de pages
$totalUsers = $userManager->getTotalUsers($search, $roleFilter, $departmentFilter);
$usersPerPage = $userManager->getUsersPerPage();
$totalPages = ceil($totalUsers / $usersPerPage);

// Récupérer les utilisateurs pour la page courante
$users = $userManager->getAllUsers($currentPage, $search, $roleFilter, $departmentFilter);

// Récupérer les statistiques
$stats = $userManager->getStatistics();
$departments = $userManager->getAllDepartments();

// Au début du fichier, après session_start();
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Supprime le message après l'avoir récupéré
}

// Inclure le header APRÈS tous les appels à header() et session
include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .main-content {
            padding: 1.5rem;
        }

        .stats-card {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .search-bar {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
        }

        .search-bar input {
            background: transparent;
            border: none;
            color: var(--text-color);
            width: 100%;
            padding: 0.5rem;
        }

        .search-bar input:focus {
            outline: none;
        }

        .filter-dropdown {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }

        .users-table {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .users-table th {
            background-color: var(--primary-bg);
            color: var(--text-muted);
            font-weight: 500;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .user-avatar {
            width: 2rem;
            height: 2rem;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-weight: 500;
        }

        .badge-role {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }

        .badge-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }

        .badge-status.active {
            background-color: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .badge-status.inactive {
            background-color: rgba(255, 69, 58, 0.1);
            color: #FF453A;
        }

        .action-btn {
            background-color: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent-color);
        }

        .btn-add-user {
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-add-user:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .pagination {
            margin-top: 1.5rem;
            justify-content: flex-end;
        }

        .page-link {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
        }

        .page-link:hover, .page-link.active {
            background-color: var(--accent-color);
            color: var(--text-color);
            border-color: var(--accent-color);
        }

        .modal-content {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .form-control {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--primary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: none;
        }

        .table tbody td {
            border-color: var(--border-color);
            padding: 1rem;
            vertical-align: middle;
            color: var(--text-color);
        }

        .user-info {
            color: var(--text-color);
        }

        .user-email, .user-department {
            color: var(--text-color) !important;
        }

        .text-end {
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            justify-content: flex-end;
            white-space: nowrap;
        }
        .action-btn {
            font-size: 1.1rem;
            padding: 0.3rem 0.5rem;
        }
        .alert {
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            position: relative;
        }
        .alert .btn-close {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
            opacity: 0.8;
        }
        .alert .btn-close:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Gestion des Utilisateurs</h4>
                    <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus"></i>
                        Ajouter un utilisateur
                    </button>
                </div>

                <!-- Message de confirmation -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total_users']; ?></div>
                            <div class="stats-label">Utilisateurs totaux</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['active_users']; ?></div>
                            <div class="stats-label">Utilisateurs actifs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['inactive_users']; ?></div>
                            <div class="stats-label">Utilisateurs inactifs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['departments_count']; ?></div>
                            <div class="stats-label">Départements</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher un utilisateur...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select filter-dropdown" id="roleFilter">
                            <option value="">Tous les rôles</option>
                            <option value="admin">Administrateur</option>
                            <option value="chef_departement">Chef de département</option>
                            <option value="coordonnateur">Coordonnateur</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="vacataire">Vacataire</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select filter-dropdown" id="departmentFilter">
                            <option value="">Tous les départements</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="users-table">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>UTILISATEUR</th>
                                <th>EMAIL</th>
                                <th>RÔLE</th>
                                <th>DÉPARTEMENT</th>
                                <th>STATUT</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-department-id="<?php echo $user['id_departement']; ?>">
                                    <td>
                                        <div class="d-flex align-items-center user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['nom_utilisateur'], 0, 2)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($user['nom_utilisateur']); ?>
                                        </div>
                                    </td>
                                    <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge-role"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                    <td class="user-department"><?php echo htmlspecialchars($user['nom_departement'] ?? 'Non assigné'); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $user['actif'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="action-btn" onclick="editUser(<?php echo $user['id']; ?>)" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn" onclick="toggleUserStatus(<?php echo $user['id']; ?>)" 
                                                title="<?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                            <i class="fas <?php echo $user['actif'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                        </button>
                                        <button class="action-btn" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-end">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select class="form-select" name="role" id="add_role" required>
                                <option value="admin">Administrateur</option>
                                <option value="chef_departement">Chef de département</option>
                                <option value="coordonnateur">Coordonnateur</option>
                                <option value="enseignant">Enseignant</option>
                                <option value="vacataire">Vacataire</option>
                            </select>
                        </div>
                        <div class="mb-3" id="add_filiere_container" style="display: none;">
                            <label class="form-label">Filière</label>
                            <select class="form-select" name="filiere_id" id="add_filiere">
                                <option value="">Sélectionner une filière</option>
                                <?php 
                                $filieres = $userManager->getAllFilieres();
                                foreach ($filieres as $filiere): 
                                ?>
                                    <option value="<?php echo $filiere['id']; ?>">
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Département</label>
                            <select class="form-select" name="department_id">
                                <option value="">Sélectionner un département</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spécialité</label>
                            <select class="form-select" name="specialite_id">
                                <option value="">Sélectionner une spécialité</option>
                                <?php 
                                try {
                                    // Récupérer les spécialités via UserManager
                                    $specialites = $userManager->getAllSpecialites();
                                    if ($specialites && count($specialites) > 0) {
                                        foreach ($specialites as $specialite): 
                                ?>
                                            <option value="<?php echo $specialite['id']; ?>">
                                                <?php echo htmlspecialchars($specialite['nom']); ?>
                                            </option>
                                        <?php endforeach;
                                    } else {
                                        echo '<option value="" disabled>Aucune spécialité disponible</option>';
                                    }
                                } catch (Exception $e) {
                                    echo '<option value="" disabled>Erreur lors du chargement des spécialités</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-save"></i>
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe (laisser vide si inchangé)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="admin">Administrateur</option>
                                <option value="chef_departement">Chef de département</option>
                                <option value="coordonnateur">Coordonnateur</option>
                                <option value="enseignant">Enseignant</option>
                                <option value="vacataire">Vacataire</option>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_filiere_container" style="display: none;">
                            <label class="form-label">Filière</label>
                            <select class="form-select" name="filiere_id" id="edit_filiere">
                                <option value="">Sélectionner une filière</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo $filiere['id']; ?>">
                                        <?php echo htmlspecialchars($filiere['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Département</label>
                            <select class="form-select" name="department_id" id="edit_department">
                                <option value="">Sélectionner un département</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spécialité</label>
                            <select class="form-select" name="specialite_id" id="edit_specialite">
                                <option value="">Sélectionner une spécialité</option>
                                <?php foreach ($specialites as $specialite): ?>
                                    <option value="<?php echo $specialite['id']; ?>">
                                        <?php echo htmlspecialchars($specialite['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction globale pour gérer l'affichage du champ filière
        function toggleFiliereField(roleSelect, filiereContainer) {
            filiereContainer.style.display = roleSelect.value === 'coordonnateur' ? 'block' : 'none';
            const filiereSelect = filiereContainer.querySelector('select');
            filiereSelect.required = roleSelect.value === 'coordonnateur';
        }

        // Fonction pour gérer la validation du champ département pour Chef de département
        function toggleDepartmentRequired(roleSelect, departmentSelect) {
            departmentSelect.required = roleSelect.value === 'chef_departement';
        }

        // Fonction de recherche et filtrage
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const departmentFilter = document.getElementById('departmentFilter');
            const tableRows = document.querySelectorAll('tbody tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedRole = roleFilter.value.toLowerCase();
                const selectedDepartment = departmentFilter.value;

                tableRows.forEach(row => {
                    const username = row.querySelector('td:first-child').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const role = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const departmentId = row.getAttribute('data-department-id');

                    const matchesSearch = username.includes(searchTerm) || 
                                       email.includes(searchTerm);
                    const matchesRole = !selectedRole || role.includes(selectedRole);
                    const matchesDepartment = !selectedDepartment || 
                                           departmentId === selectedDepartment;

                    row.style.display = matchesSearch && matchesRole && matchesDepartment ? 
                                      '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterTable);
            roleFilter.addEventListener('change', filterTable);
            departmentFilter.addEventListener('change', filterTable);

            // Gestion de l'affichage du champ filière (Ajout et Modification)
            const addRoleSelect = document.getElementById('add_role');
            const addFiliereContainer = document.getElementById('add_filiere_container');
            const editRoleSelect = document.getElementById('edit_role');
            const editFiliereContainer = document.getElementById('edit_filiere_container');

            addRoleSelect.addEventListener('change', () => toggleFiliereField(addRoleSelect, addFiliereContainer));
            editRoleSelect.addEventListener('change', () => toggleFiliereField(editRoleSelect, editFiliereContainer));

            // Initialiser l'état des champs filière
            toggleFiliereField(addRoleSelect, addFiliereContainer);
            toggleFiliereField(editRoleSelect, editFiliereContainer);

            // Gestion de la validation du champ département pour Chef de département (Ajout)
            const addDepartmentSelect = document.querySelector('#addUserModal select[name="department_id"]');
            addRoleSelect.addEventListener('change', () => toggleDepartmentRequired(addRoleSelect, addDepartmentSelect));

            // Initialiser l'état du champ département (Ajout)
            toggleDepartmentRequired(addRoleSelect, addDepartmentSelect);

             // Gestion de la validation du champ département pour Chef de département (Modification)
             const editDepartmentSelect = document.querySelector('#editUserModal select[name="department_id"]');
             editRoleSelect.addEventListener('change', () => toggleDepartmentRequired(editRoleSelect, editDepartmentSelect));

             // Initialiser l'état du champ département (Modification) - sera géré dans editUser()

        });

        // Fonction pour éditer un utilisateur
        function editUser(userId) {
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_username').value = user.nom_utilisateur;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_role').value = user.role;
                    document.getElementById('edit_department').value = user.id_departement || '';
                    document.getElementById('edit_filiere').value = user.id_filiere || '';
                    document.getElementById('edit_specialite').value = user.id_specialite || '';
                    
                    // Mettre à jour l'affichage du champ filière
                    toggleFiliereField(document.getElementById('edit_role'), document.getElementById('edit_filiere_container'));

                    // Mettre à jour l'état requis du champ département
                    toggleDepartmentRequired(document.getElementById('edit_role'), document.querySelector('#editUserModal select[name="department_id"]'));
                    
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                });
        }

        // Fonction pour supprimer un utilisateur
        function deleteUser(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fonction pour activer/désactiver un utilisateur
        function toggleUserStatus(userId) {
            if (confirm('Êtes-vous sûr de vouloir modifier le statut de cet utilisateur ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fonction pour mettre à jour les spécialités en fonction du département
        function updateSpecialites(departmentId) {
            const specialiteSelect = document.getElementById('specialite_id');
            specialiteSelect.innerHTML = '<option value="">Sélectionner une spécialité</option>';
            
            if (!departmentId) return;

            // Faire une requête AJAX pour récupérer les spécialités du département
            fetch(`get_specialites.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(specialites => {
                    specialites.forEach(specialite => {
                        const option = document.createElement('option');
                        option.value = specialite.id;
                        option.textContent = specialite.nom;
                        specialiteSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Erreur:', error));
        }

        // Mettre à jour les spécialités lors du chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const departmentId = document.getElementById('department_id').value;
            if (departmentId) {
                updateSpecialites(departmentId);
            }
        });
    </script>
</body>
</html>
