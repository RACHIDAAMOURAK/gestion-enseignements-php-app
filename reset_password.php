<?php
require_once 'config/database.php';
require_once 'classes/Authentication.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$error_message = '';
$success_message = '';

if (!empty($token)) {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Authentication($db);
    
    // Vérifier si le token est valide
    $result = $auth->verifyResetToken($token);
    $valid_token = $result["success"];
    
    if (!$valid_token) {
        $error_message = "Token invalide ou expiré.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : "";
    
    if (empty($password) || empty($confirm_password)) {
        $error_message = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $result = $auth->resetPassword($token, $password);
        if ($result["success"]) {
            $success_message = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
        } else {
            $error_message = $result["message"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .reset-container {
            width: 400px;
            padding: 30px;
            background-color: #1c2841;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        h3 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-control {
            background-color: transparent;
            border: 1px solid #3a4c6d;
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
            border-color: #31b7d1;
            box-shadow: none;
        }
        
        .btn-reset {
            background-color: #31b7d1;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            background-color: rgb(171, 226, 237);
            transform: translateY(-2px);
        }
        
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h3>Réinitialisation du mot de passe</h3>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
                <div class="mt-3">
                    <a href="login.php" class="btn btn-primary">Retour à la connexion</a>
                </div>
            </div>
        <?php elseif ($valid_token): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token); ?>">
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Nouveau mot de passe" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                </div>
                <button type="submit" class="btn-reset">
                    <i class="fas fa-key"></i>
                    Réinitialiser le mot de passe
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 