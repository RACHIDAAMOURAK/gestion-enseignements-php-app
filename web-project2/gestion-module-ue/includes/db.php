<?php 
$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "projet_web"; // Remplacez par le nom de votre base de données

try {
    $conn = mysqli_connect($db_server, $db_user, $db_password, $db_name);
    if (!$conn) {
        throw new Exception("Erreur de connexion : " . mysqli_connect_error());
    }
    // Définir le jeu de caractères
    mysqli_set_charset($conn, "utf8mb4");
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
