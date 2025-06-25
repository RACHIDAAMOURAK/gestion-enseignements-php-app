<?php
// Configuration de l'encodage
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Configuration de la base de données
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("La connexion a échoué : " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Configuration de l'encodage pour la connexion
$conn->query("SET NAMES utf8mb4");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET collation_connection = 'utf8mb4_unicode_ci'");

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?> 