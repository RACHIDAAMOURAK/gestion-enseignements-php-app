<?php
require_once "../gestion-module-ue/includes/db.php";
header('Content-Type: application/json');

$id_ue = isset($_GET['ue']) ? intval($_GET['ue']) : 0;
$id_filiere = isset($_GET['filiere']) ? intval($_GET['filiere']) : 0;

if ($id_ue && $id_filiere) {
    $query = "SELECT 1 FROM ue_filiere 
              WHERE id_ue = ? AND id_filiere = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_ue, $id_filiere);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    $valid = mysqli_stmt_num_rows($stmt) > 0;
    echo json_encode(['valid' => $valid]);
} else {
    echo json_encode(['valid' => false]);
}
?> 