<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3306'); // Port MySQL standard
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projet_web');

// Connexion à la base de données
function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En production, utilisez un système de journalisation plutôt que d'afficher l'erreur
        die('Erreur de connexion à la base de données: ' . $e->getMessage());
    }
}

// Fonctions utilitaires
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté et a le rôle approprié
function checkUserRole($allowedRoles = []) {
    session_start();
    
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    
    // Si des rôles sont spécifiés, vérifier que l'utilisateur a un rôle autorisé
    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: /access-denied.php');
        exit;
    }
    
    return $_SESSION;
}
?>