<?php
// Fichier pour récupérer les informations d'un utilisateur (utilisé par AJAX)
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID utilisateur non fourni']);
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/UserManager.php';

// Instancier la base de données et le gestionnaire d'utilisateurs
$database = new Database();
$db = $database->getConnection();
$userManager = new UserManager($db);

// Récupérer l'utilisateur
$user = $userManager->getUserById($_GET['id']);

if ($user) {
    // Retourner les données de l'utilisateur
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Utilisateur non trouvé']);
}