<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/RoleManager.php';
include_once '../classes/SessionManager.php';
include_once '../includes/header.php';

// Instancier la base de données et les gestionnaires
$database = new Database();
$db = $database->getConnection();
$roleManager = new RoleManager($db);
$sessionManager = new SessionManager();

// Traitement des mises à jour des permissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_permissions'])) {
    $role = $_POST['role'];
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    if ($roleManager->updateRolePermissions($role, $permissions)) {
        $message = "Les permissions ont été mises à jour avec succès.";
        $status = "success";
    } else {
        $message = "Erreur lors de la mise à jour des permissions.";
        $status = "error";
    }
}

// Récupérer la liste des rôles et toutes les permissions disponibles
$roles = ['chef_departement', 'coordonnateur', 'enseignant', 'vacataire'];
$allPermissions = $roleManager->getAllPermissions();

// Si un rôle est sélectionné, récupérer ses permissions
$selectedRole = isset($_GET['role']) ? $_GET['role'] : 'chef_departement';
$rolePermissions = $roleManager->getRolePermissions($selectedRole);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rôles - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
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

        .role-card {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .permission-group {
            background-color: var(--primary-bg);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .permission-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .permission-item:last-child {
            border-bottom: none;
        }

        .form-check-input {
            background-color: var(--primary-bg);
            border-color: var(--text-muted);
        }

        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .role-selector {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .role-selector:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn-save {
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-save:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(52, 199, 89, 0.1);
            border-color: rgba(52, 199, 89, 0.2);
            color: #34C759;
        }

        .alert-error {
            background-color: rgba(255, 69, 58, 0.1);
            border-color: rgba(255, 69, 58, 0.2);
            color: #FF453A;
        }

        .btn-new {
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
        }

        .btn-new:hover {
            background-color: var(--accent-color);
            color: var(--text-color);
        }

        .btn-new i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Gestion des Rôles et Permissions</h4>
                    <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#newPermissionModal">
                        <i class="fas fa-plus"></i>
                        Nouvelle Permission
                    </button>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $status; ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="role-card">
                    <form method="GET" class="mb-4">
                        <select name="role" class="role-selector" onchange="this.form.submit()">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role; ?>" <?php echo $selectedRole === $role ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="role" value="<?php echo $selectedRole; ?>">
                        <div class="permission-group">
                            <h5 class="mb-3">Permissions du rôle : <?php echo ucfirst(str_replace('_', ' ', $selectedRole)); ?></h5>
                            <?php 
                            $rolePermissionIds = $roleManager->getRolePermissions($selectedRole);
                            foreach ($allPermissions as $permission): 
                                if (in_array($permission['id'], $rolePermissionIds)):
                            ?>
                                <div class="permission-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo $permission['id']; ?>"
                                               <?php echo in_array($permission['id'], $rolePermissionIds) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            <?php echo $permission['nom']; ?>
                                            <small class="text-muted d-block"><?php echo $permission['description']; ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>

                        <div class="permission-group mt-4">
                            <h5 class="mb-3">Permissions disponibles</h5>
                            <?php 
                            foreach ($allPermissions as $permission): 
                                if (!in_array($permission['id'], $rolePermissionIds)):
                            ?>
                                <div class="permission-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo $permission['id']; ?>">
                                        <label class="form-check-label">
                                            <?php echo $permission['nom']; ?>
                                            <small class="text-muted d-block"><?php echo $permission['description']; ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>

                        <button type="submit" name="update_permissions" class="btn-action mt-4">
                            <i class="fas fa-save"></i>
                            Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter une nouvelle permission -->
    <div class="modal fade" id="newPermissionModal" tabindex="-1" aria-labelledby="newPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--secondary-bg); color: var(--text-color);">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="newPermissionModalLabel">Nouvelle Permission</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="add_permission.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permissionName" class="form-label">Nom de la permission</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="permissionName" 
                                   name="nom" 
                                   required
                                   style="background-color: var(--primary-bg); 
                                          color: var(--text-color); 
                                          border: 1px solid var(--border-color);
                                          padding: 0.75rem;
                                          border-radius: 0.5rem;">
                        </div>
                        <div class="mb-3">
                            <label for="permissionDescription" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="permissionDescription" 
                                      name="description" 
                                      rows="3" 
                                      required
                                      style="background-color: var(--primary-bg); 
                                             color: var(--text-color); 
                                             border: 1px solid var(--border-color);
                                             padding: 0.75rem;
                                             border-radius: 0.5rem;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-check"></i>
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html> 