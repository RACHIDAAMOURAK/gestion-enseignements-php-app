<?php
session_start();
include_once 'db.php';

// Vérification des permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$pdo = connectDB();
$id_enseignant = $_SESSION['user_id'];
$annee_actuelle = date('Y') . '-' . (date('Y') + 1);

// Récupérer la charge horaire de l'enseignant
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(volume_horaire), 0) as total_heures
    FROM historique_affectations 
    WHERE id_utilisateur = ? 
    AND role = 'enseignant'
    AND annee_universitaire = ?
");
$stmt->execute([$id_enseignant, $annee_actuelle]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$charge_totale = $result['total_heures'];

// Vérifier si la charge est suffisante
$charge_minimale = 192;
$charge_suffisante = $charge_totale >= $charge_minimale;

if (!$charge_suffisante) {
    // Vérifier si une notification existe déjà
    $stmt = $pdo->prepare("
        SELECT id 
        FROM notifications 
        WHERE id_utilisateur = ? 
        AND type = 'warning' 
        AND message LIKE ? 
        AND date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$id_enseignant, '%charge horaire insuffisante%']);
    
    if ($stmt->rowCount() == 0) {
        // Créer une nouvelle notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                id_utilisateur, 
                titre, 
                message, 
                type, 
                statut, 
                date_creation
            ) VALUES (?, ?, ?, 'warning', 'non_lu', NOW())
        ");
        
        $titre = "Charge horaire insuffisante";
        $message = sprintf(
            "Votre charge horaire actuelle (%dh) est inférieure au minimum requis (%dh). Il vous manque %dh.",
            $charge_totale,
            $charge_minimale,
            $charge_minimale - $charge_totale
        );
        
        $stmt->execute([$id_enseignant, $titre, $message]);
    }
}

// Retourner le statut de la charge horaire
header('Content-Type: application/json');
echo json_encode([
    'charge_totale' => $charge_totale,
    'charge_minimale' => $charge_minimale,
    'charge_suffisante' => $charge_suffisante,
    'heures_manquantes' => $charge_minimale - $charge_totale
]);
?> 