<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vacataire') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacataire_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Vérification du mot de passe actuel
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->execute([$vacataire_id]);
        $current_hash = $stmt->fetchColumn();
        
        if (password_verify($current_password, $current_hash)) {
            if ($new_password === $confirm_password) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                $stmt->execute([$new_hash, $vacataire_id]);
                
                $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
            } else {
                $_SESSION['error_message'] = "Les mots de passe ne correspondent pas.";
            }
        } else {
            $_SESSION['error_message'] = "Mot de passe actuel incorrect.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors du changement de mot de passe : " . $e->getMessage();
    }
    
    header('Location: dashboard.php');
    exit();
}
?> 