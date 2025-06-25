<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vacataire') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacataire_id = $_SESSION['user_id'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $specialite = $_POST['specialite'];
    $adresse = $_POST['adresse'];
    
    try {
        $pdo->beginTransaction();
        
        // Mise à jour des informations utilisateur
        $stmt = $pdo->prepare("
            UPDATE utilisateurs 
            SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?
            WHERE id = ?
        ");
        $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $vacataire_id]);
        
        // Mise à jour de la spécialité
        $stmt = $pdo->prepare("DELETE FROM utilisateur_specialites WHERE id_utilisateur = ?");
        $stmt->execute([$vacataire_id]);
        
        $stmt = $pdo->prepare("INSERT INTO utilisateur_specialites (id_utilisateur, id_specialite) VALUES (?, ?)");
        $stmt->execute([$vacataire_id, $specialite]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Profil mis à jour avec succès.";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
    }
    
    header('Location: dashboard.php');
    exit();
}
?> 