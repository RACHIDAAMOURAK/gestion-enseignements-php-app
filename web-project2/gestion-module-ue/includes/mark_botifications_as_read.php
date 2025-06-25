<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure the path to your database connection file is correct
// If your connection is in config.php, use require_once 'config.php';
require_once 'db.php'; 

header('Content-Type: application/json'); // Respond in JSON

// Check if the user is logged in and is a coordinator (optional but recommended)
// Assuming $_SESSION['role'] is set upon login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'coordonnateur') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark the unread notifications for the coordinator as read
// Using the table Notifications_Coordonnateur and column id_utilisateur
$query = "UPDATE Notifications_Coordonnateur SET is_read = 1 WHERE id_utilisateur = ? AND is_read = 0";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // Check if any rows were affected (notifications updated)
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notifications marquées comme lues.']);
        } else {
            // No unread notifications found for this user
             echo json_encode(['success' => true, 'message' => 'Aucune nouvelle notification à marquer comme lue.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour : ' . $stmt->error]);
    }
    $stmt->close();
} else {
     echo json_encode(['success' => false, 'message' => 'Erreur de préparation de la requête : ' . $conn->error]);
}

$conn->close();
?>