<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Compter les notifications non lues
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS count FROM notifications WHERE id_utilisateur = ? AND statut = 'non_lu'");
    $countStmt->execute([$userId]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les notifications
    $notifStmt = $pdo->prepare("
        SELECT id, titre, message, type, statut, 
               DATE_FORMAT(date_creation, '%d/%m/%Y %H:%i') as date_creation
        FROM notifications
        WHERE id_utilisateur = ?
        ORDER BY date_creation DESC
        LIMIT 10
    ");
    $notifStmt->execute([$userId]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$countResult['count'],
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 