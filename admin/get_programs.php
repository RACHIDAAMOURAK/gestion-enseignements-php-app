<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID du département est fourni
if (!isset($_GET['department_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID département non fourni']);
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/DepartmentManager.php';

// Instancier la base de données et le gestionnaire
$database = new Database();
$db = $database->getConnection();
$departmentManager = new DepartmentManager($db);

// Récupérer les filières
$programs = $departmentManager->getDepartmentPrograms($_GET['department_id']);
echo json_encode($programs);