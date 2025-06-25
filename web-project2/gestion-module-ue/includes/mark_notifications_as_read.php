<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure the path to your database connection file is correct
// If your connection is in config.php, use require_once 'config.php';
require_once 'db.php'; 

header('Content-Type: application/json'); // Respond in JSON

// Vérifier si l'utilisateur est connecté et est un coordinateur
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'coordonnateur') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$pdo = connectDB();

try {
    // Marquer toutes les notifications non lues comme lues
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET statut = 'lu', 
            date_lecture = NOW() 
        WHERE id_utilisateur = ? 
        AND statut = 'non_lu'
    ");
    
    $result = $stmt->execute([$id_utilisateur]);
    
    if ($result) {
        $count = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => 'Notifications marquées comme lues',
            'count' => $count
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la mise à jour des notifications'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>