<?php
session_start();

// Déterminer la page d'accueil en fonction du rôle
$home_page = 'login.php'; // Par défaut, rediriger vers la page de connexion
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            $home_page = 'admin/dashboard.php';
            break;
        case 'chef_departement':
            $home_page = 'projet_web_SF/chef_departement_interface/lister_professeurs_departement.php';
            break;
        case 'coordonnateur':
            $home_page = '/projet_web/web-project2/gestion-module-ue/index.php';
            break;
        case 'enseignant':
            $home_page = '/projet_web/projet_web_SF/enseignat_interfce/mes_modules.php';
            break;
        case 'vacataire':
            $home_page = 'interface_vacataire/dashboard.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - Gestion des Affectations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .access-denied-container {
            width: 800px;
            height: 500px;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            overflow: hidden;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .row {
            height: 100%;
            margin: 0;
        }
        
        .left-panel {
            background-color: #1f3352;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            height: 100%;
            padding: 2rem;
        }
        
        .right-panel {
            background-color: #1c2841;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            background: #1c2841;
            width: 120px;
            height: 120px;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        
        .logo i {
            color: #31b7d1;
            font-size: 50px;
        }
        
        .error-content {
            width: 100%;
            max-width: 320px;
            text-align: center;
        }
        
        .error-content h3 {
            margin-bottom: 25px;
            text-align: center;
            color: white;
            font-weight: 600;
        }
        
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-action {
            background-color: #31b7d1;
            color: white !important;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-action:hover {
            background-color: rgb(171, 226, 237);
            transform: translateY(-2px);
            color: white !important;
        }
        
        .btn-secondary {
            background-color: transparent;
            color: #31b7d1 !important;
            border: 1px solid #31b7d1;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background-color: rgba(49, 183, 209, 0.1);
            color: rgb(171, 226, 237) !important;
            border-color: rgb(171, 226, 237);
        }

        @media (max-width: 768px) {
            .access-denied-container {
                width: 95%;
                height: auto;
                min-height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="row">
            <div class="col-md-6 left-panel">
                <div class="logo">
                    <i class="fas fa-ban"></i>
                </div>
                <h2 class="mt-4">Accès Refusé</h2>
                <p class="text-center">Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.</p>
            </div>
            <div class="col-md-6 right-panel">
                <div class="error-content">
                    <h3>Accès Non Autorisé</h3>
                    <p>Veuillez contacter votre administrateur si vous pensez qu'il s'agit d'une erreur.</p>
                    <div class="btn-container">
                        <a href="<?php echo $home_page; ?>" class="btn-action">
                            <i class="fas fa-home me-2"></i>Retour à l'accueil
                        </a>
                        <a href="logout.php" class="btn-secondary">
                            <i class="fas fa-sign-out-alt me-2"></i>Se déconnecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 