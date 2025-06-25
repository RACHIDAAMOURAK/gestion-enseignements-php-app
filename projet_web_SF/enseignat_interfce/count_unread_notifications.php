<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM notifications WHERE id_utilisateur = ? AND statut = 'non_lu'");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'count' => (int)$result['count']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 