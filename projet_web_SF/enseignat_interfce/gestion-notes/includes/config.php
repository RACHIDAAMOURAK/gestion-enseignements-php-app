<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit;
}

// Configuration des chemins
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['xlsx', 'xls', 'csv']);

// Création du dossier d'upload s'il n'existe pas
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Fonctions utilitaires
function isAllowedFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}
?> 