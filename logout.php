
// Page de déconnexion
<?php
// Inclure le middleware d'authentification
include_once 'authenticate.php';

// Instancier la base de données
$database = new Database();
$db = $database->getConnection();

// Instancier l'authentification
$auth = new Authentication($db);

// Si l'utilisateur est connecté
if (isLoggedIn()) {
    // Déconnecter l'utilisateur
    $auth->logout($_SESSION['session_id']);
    
    // Détruire la session
    session_unset();
    session_destroy();
}

// Rediriger vers la page de connexion
header("Location: login.php");
exit;
?>

