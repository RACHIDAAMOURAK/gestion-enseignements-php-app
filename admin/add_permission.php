<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/RoleManager.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    
    try {
        $query = "INSERT INTO permissions (nom, description) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nom, $description])) {
            $_SESSION['message'] = "La permission a été créée avec succès.";
            $_SESSION['status'] = "success";
        } else {
            $_SESSION['message'] = "Erreur lors de la création de la permission.";
            $_SESSION['status'] = "error";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Code d'erreur pour duplicate entry
            $_SESSION['message'] = "Une permission avec ce nom existe déjà.";
        } else {
            $_SESSION['message'] = "Erreur lors de la création de la permission.";
        }
        $_SESSION['status'] = "error";
    }
}

// Rediriger vers la page des rôles
header("Location: roles.php");
exit; 