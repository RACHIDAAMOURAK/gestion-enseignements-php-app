<?php
session_start();
include_once 'db.php';
$pdo = connectDB();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Traitement de la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    try {
        // Mise à jour de toutes les notifications non lues
        $stmt = $pdo->prepare("UPDATE notifications SET statut = 'lu', date_lecture = NOW() WHERE id_utilisateur = ? AND statut = 'non_lu'");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            $count = $stmt->rowCount(); // Nombre de lignes affectées
            echo json_encode(['success' => true, 'count' => $count]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 