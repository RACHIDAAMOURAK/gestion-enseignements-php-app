<?php
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID du département est fourni
if (!isset($_GET['department_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du département manquant']);
    exit;
}

$department_id = intval($_GET['department_id']);

try {
    // Préparer et exécuter la requête
    $stmt = $pdo->prepare("
        SELECT id, nom 
        FROM specialites 
        WHERE id_departement = ? 
        ORDER BY nom
    ");
    $stmt->execute([$department_id]);
    $specialites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Renvoyer les résultats en JSON
    header('Content-Type: application/json');
    echo json_encode($specialites);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des spécialités']);
} 