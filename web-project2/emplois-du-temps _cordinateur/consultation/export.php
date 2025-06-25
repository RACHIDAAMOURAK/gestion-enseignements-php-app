<?php
require_once '../includes/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Récupération des paramètres
    $id_filiere = isset($_GET['id_filiere']) ? intval($_GET['id_filiere']) : null;
    $semestre = isset($_GET['semestre']) ? intval($_GET['semestre']) : null;
    $annee = isset($_GET['annee']) ? $_GET['annee'] : null;

    if (!$id_filiere || !$semestre || !$annee) {
        throw new Exception("Paramètres manquants");
    }

    // Récupération des informations de la filière
    $stmt = $conn->prepare("SELECT nom FROM filieres WHERE id = ?");
    $stmt->bind_param("i", $id_filiere);
    $stmt->execute();
    $result = $stmt->get_result();
    $filiere = $result->fetch_assoc();

    if (!$filiere) {
        throw new Exception("Filière non trouvée");
    }

    // Création du classeur Excel basique
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // En-têtes simples
    $sheet->setCellValue('A1', 'Horaire');
    $sheet->setCellValue('B1', 'Lundi');
    $sheet->setCellValue('C1', 'Mardi');
    $sheet->setCellValue('D1', 'Mercredi');
    $sheet->setCellValue('E1', 'Jeudi');
    $sheet->setCellValue('F1', 'Vendredi');

    // Horaires
    $sheet->setCellValue('A2', '08:00 - 10:00');
    $sheet->setCellValue('A3', '10:00 - 12:00');
    $sheet->setCellValue('A4', '14:00 - 16:00');
    $sheet->setCellValue('A5', '16:00 - 18:00');

    // Récupération des séances
    $query = "SELECT s.*, 
                 ue.code as code_ue, 
                 ue.intitule as intitule_ue,
                 CONCAT(g.type, ' ', g.numero) as nom_groupe,
                 s.salle,
                 COALESCE(ha.nom_utilisateur, '??') as nom_enseignant,
                 s.type as type_cours
              FROM seances s
              LEFT JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
              LEFT JOIN groupes g ON s.id_groupe = g.id
              LEFT JOIN historique_affectations ha ON (ue.id = ha.id_unite_enseignement AND s.type = ha.type_cours)
              WHERE s.id_emploi_temps = (
                  SELECT id FROM emplois_temps 
                  WHERE id_filiere = ? AND semestre = ? AND annee_universitaire = ?
              )
              ORDER BY s.jour, s.heure_debut";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $id_filiere, $semestre, $annee);
    $stmt->execute();
    $result = $stmt->get_result();

    // Mapping simple
    $jour_map = [
        'Lundi' => 'B',
        'Mardi' => 'C',
        'Mercredi' => 'D',
        'Jeudi' => 'E',
        'Vendredi' => 'F'
    ];
    
    $heure_map = [
        '08:00' => 2,
        '10:00' => 3,
        '14:00' => 4,
        '16:00' => 5
    ];

    // Remplissage des séances
    while ($seance = $result->fetch_assoc()) {
        $colonne = $jour_map[$seance['jour']];
        $ligne = $heure_map[substr($seance['heure_debut'], 0, 5)];
        
        $contenu = $seance['intitule_ue'] . ' - ' . 
                   $seance['type_cours'] .
                   ($seance['type_cours'] !== 'CM' ? ' - ' . $seance['nom_groupe'] : '') .
                   ' - ' . $seance['salle'];
        
        $sheet->setCellValue($colonne . $ligne, $contenu);
    }

    // Largeur des colonnes
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setWidth(30);
    }

    // Création du fichier
    $writer = new Xlsx($spreadsheet);
    
    // Nom du fichier simplifié
    $filename = 'EDT_' . $semestre . '_' . $annee . '.xlsx';

    // Headers basiques
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Envoi du fichier
    ob_end_clean(); // Nettoie le buffer de sortie
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "Une erreur est survenue lors de l'export : " . $e->getMessage();
    exit;
} 