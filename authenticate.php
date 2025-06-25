// Fichier: authenticate.// Middleware pour vérifier l'authentification sur toutes les pages protégées
<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
include_once 'config/database.php';
include_once 'classes/Authentication.php';

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    // Vérifier si la session existe
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
        // Instancier la base de données
        $database = new Database();
        $db = $database->getConnection();
        
        // Instancier l'authentification
        $auth = new Authentication($db);
        
        // Valider la session
        $result = $auth->validateSession($_SESSION['session_id']);
        
        // Si la session est valide
        if ($result["success"]) {
            return true;
        } else {
            // Détruire la session si elle n'est plus valide
            session_unset();
            session_destroy();
        }
    }
    return false;
}

// Fonction pour vérifier l'accès au rôle
function checkRoleAccess($allowed_roles) {
    if (!isLoggedIn()) {
        // Rediriger vers la page de connexion
        header("Location: login.php");
        exit;
    }
    
    // Vérifier si le rôle de l'utilisateur est autorisé
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Rediriger vers une page d'erreur d'accès
        header("Location: access_denied.php");
        exit;
    }
    
    return true;
}

