<?php
// Page de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inclure les fichiers nécessaires
    include_once 'config/database.php';
    include_once 'classes/Authentication.php';
    
    // Instancier la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Instancier l'authentification
    $auth = new Authentication($db);
    
    // Obtenir les données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    
    // Si les champs ne sont pas vides
    if (!empty($username) && !empty($password)) {
        // Tenter de se connecter
        $result = $auth->login($username, $password);
        
        // Si la connexion est réussie
        if ($result["success"]) {
            // Vérifier si le compte est actif
            if ($result["user"]["actif"] == 1) {
                // Démarrer la session PHP
                session_start();
                
                // Stocker les informations de session
                $_SESSION['user_id'] = $result["user"]["id"];
                $_SESSION['username'] = $result["user"]["nom_utilisateur"];
                $_SESSION['email'] = $result["user"]["email"];
                $_SESSION['role'] = $result["user"]["role"];
                $_SESSION['department_id'] = $result["user"]["id_departement"];
                $_SESSION['filiere_id'] = $result["user"]["id_filiere"];
                $_SESSION['session_id'] = $result["session_id"];
                
                // Rediriger selon le rôle
                switch ($result["user"]["role"]) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'chef_departement':
                        header("Location: projet_web_SF/chef_departement_interface/lister_professeurs_departement.php");
                        break;
                    case 'coordonnateur':
                        header("Location: /projet_web/web-project2/gestion-module-ue/index.php");
                        break;
                    case 'enseignant':
                        header("Location:/projet_web/projet_web_SF/enseignat_interfce/mes_modules.php");
                        break;
                    case 'vacataire':
                        header("Location: interface_vacataire/dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                        break;
                }
                exit;
            } else {
                // Message d'erreur pour compte désactivé
                $error_message = "Votre compte est désactivé. Veuillez contacter l'administrateur.";
            }
        } else {
            // Message d'erreur d'authentification
            $error_message = $result["message"];
        }
    } else {
        $error_message = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion des Affectations</title>
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
        
        .login-container {
            width: 800px;
            height: 500px;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .row {
            height: 100%;
            margin: 0;
        }
        
        .left-panel {
            background-color:  #1f3352;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            height: 100%;
        }
        
        .right-panel {
            background-color: #1c2841;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 20px;
        }
        
        .logo {
            text-align: center;
            background:  #1c2841;
            width: 120px;
            height: 120px;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
        }
        
        .logo img, .logo i {
            max-width: 70px;
            color:  #31b7d1;
            font-size: 50px;
        }
        
        .login-form {
            width: 100%;
            max-width: 320px;
        }
        
        .login-form h3 {
            margin-bottom: 25px;
            text-align: center;
            color: white;
            font-weight: 600;
        }
        
        .form-control {
            background-color: transparent;
            border: 1px solid  #3a4c6d;
            color: white;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .form-control::placeholder {
            color: #7086ab;
        }
        
        .form-control:focus {
            background-color: transparent;
            color: white;
            border-color:  #31b7d1;
            box-shadow: none;
        }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .input-group-text {
            background-color:transparent;
            border: 1px solid #3a4c6d;
            border-left: none;
            color:  #7086ab;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
        }
        
        .input-group .form-control {
            margin-bottom: 0;
            border-right: none;
        }

        .input-group-text i {
            font-size: 16px;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login {
            background-color:#31b7d1;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: rgb(171, 226, 237);
            transform: translateY(-2px);
        }
        
        .text-muted {
            color: #7086ab !important;
            font-size: 12px;
        }
        
        .form-check-input {
            background-color: transparent;
            border: 1px solid #3a4c6d;
        }
        
        .form-check-input:checked {
            background-color: #31b7d1;
            border-color: #31b7d1;
        }
        
        a {
            color:  #31b7d1 !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        a:hover {
            color:rgb(171, 226, 237) !important;
        }
        
        .form-label {
            color: white;
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row">
            <!-- Left Side - Logo Only -->
            <div class="col-md-6 left-panel">
                <div class="logo">
                    <img src="LOGO.png.png" alt="Logo">
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="col-md-6 right-panel">
                <div class="login-form">
                    <h3>Se connecter</h3>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur ou email</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter nom d'utilisateur ou email" required>
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter mot de passe" required>
                                <span class="input-group-text"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="forgot_password.php">Mot de passe oublié?</a>
                        </div>
                        
                        <button type="submit" class="btn-action w-100">
                            <i class="fas fa-sign-in-alt"></i>
                            Se connecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelector('.input-group-text .fa-eye').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>