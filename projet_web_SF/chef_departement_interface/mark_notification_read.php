<?php
session_start();
include_once 'db.php';
$pdo = connectDB();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la requête et des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notif_id'])) {
    $userId = $_SESSION['user_id'];
    $notifId = intval($_POST['notif_id']);
    
    try {
        // Mise à jour du statut de la notification spécifique
        $stmt = $pdo->prepare("UPDATE notifications SET statut = 'lu', date_lecture = NOW() WHERE id = ? AND id_utilisateur = ?");
        $result = $stmt->execute([$notifId, $userId]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
}
?> 