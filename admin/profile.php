<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/UserManager.php';
include_once '../classes/SessionManager.php';
include_once '../includes/header.php';

// Instancier la base de données et les gestionnaires
$database = new Database();
$db = $database->getConnection();
$userManager = new UserManager($db);
$sessionManager = new SessionManager();

// Récupérer les informations de l'utilisateur
$user = $userManager->getUserById($_SESSION['user_id']);

// Traitement des formulaires
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = ['success' => false, 'message' => ''];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                // Les mises à jour du profil ne sont plus autorisées
                $response = [
                    'success' => false,
                    'message' => 'La modification du nom d\'utilisateur et de l\'email n\'est pas autorisée.'
                ];
                break;

            case 'change_password':
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $response = $userManager->changePassword(
                        $_SESSION['user_id'],
                        $_POST['current_password'],
                        $_POST['new_password']
                    );
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Les nouveaux mots de passe ne correspondent pas.'
                    ];
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Administration</title>
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

        .profile-section {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--text-color);
            margin-right: 1.5rem;
        }

        .profile-info h2 {
            margin: 0;
            color: var(--text-color);
        }

        .profile-info p {
            color: var(--text-muted);
            margin: 0;
        }

        .form-control {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background-color: var(--primary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: none;
        }

        .form-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .btn-action {
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .badge-role {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
        }

        .alert-success {
            background-color: rgba(52, 199, 89, 0.1);
            border: 1px solid rgba(52, 199, 89, 0.2);
            color: #34C759;
        }

        .alert-danger {
            background-color: rgba(255, 69, 58, 0.1);
            border: 1px solid rgba(255, 69, 58, 0.2);
            color: #FF453A;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Mon Profil</h4>
                </div>

                <?php if (isset($response)): ?>
                    <div class="alert alert-<?php echo $response['success'] ? 'success' : 'danger'; ?>" role="alert">
                        <?php echo $response['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Information -->
                <div class="profile-section">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['nom_utilisateur'], 0, 2)); ?>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['nom_utilisateur']); ?></h2>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="badge-role"><?php echo ucfirst($user['role']); ?></p>
                        </div>
                    </div>

                    <!-- Update Profile Form -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($user['nom_utilisateur']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                        </div>
                        <div class="text-muted mb-3">
                            <small><i class="fas fa-info-circle"></i> Le nom d'utilisateur et l'email ne peuvent pas être modifiés.</small>
                        </div>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-save"></i>
                            Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="profile-section">
                    <h5 class="mb-4">Changer le mot de passe</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-key"></i>
                            Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 