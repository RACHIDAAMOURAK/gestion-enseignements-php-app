<?php
require_once "../includes/config.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    if (!isset($_GET['id_enseignant'])) {
        throw new Exception("ID de l'enseignant manquant");
    }

    $id_enseignant = intval($_GET['id_enseignant']);

    // Récupérer le nom de l'enseignant
    $stmt = $conn->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt->bind_param("i", $id_enseignant);
    $stmt->execute();
    $result = $stmt->get_result();
    $enseignant = $result->fetch_assoc();

    if (!$enseignant) {
        throw new Exception("Enseignant non trouvé");
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
                 f.nom as nom_filiere,
                 et.semestre,
                 s.type as type_cours
              FROM utilisateurs u
              JOIN historique_affectations ha ON u.id = ha.id_utilisateur
              JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
              JOIN seances s ON ue.id = s.id_unite_enseignement AND s.type = ha.type_cours
              JOIN emplois_temps et ON s.id_emploi_temps = et.id
              JOIN filieres f ON et.id_filiere = f.id
              LEFT JOIN groupes g ON s.id_groupe = g.id
              WHERE u.id = ? 
              AND et.statut = 'actif'
              ORDER BY s.jour, s.heure_debut";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_enseignant);
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
    $filename = 'EDT_' . $enseignant['nom'] . '.xlsx';

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