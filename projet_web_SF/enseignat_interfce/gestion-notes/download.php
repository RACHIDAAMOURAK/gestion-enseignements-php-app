<?php
require_once 'includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    if (!isset($_GET['id_ue']) || !isset($_GET['type_session'])) {
        throw new Exception("Paramètres manquants");
    }

    $id_unite_enseignement = intval($_GET['id_ue']);
    $type_session = $_GET['type_session'];

    // Récupération des informations de l'UE et du fichier
    $stmt = $conn->prepare("SELECT ue.*, 
                           fn.nom_fichier,
                           fn.date_upload
                           FROM unites_enseignement ue
                           JOIN fichiers_notes fn ON ue.id = fn.id_unite_enseignement
                           WHERE ue.id = ? AND fn.type_session = ?
                           ORDER BY fn.date_upload DESC
                           LIMIT 1");
    $stmt->execute([$id_unite_enseignement, $type_session]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("UE non trouvée");
    }

    // Récupération des notes
    $stmt = $conn->prepare("SELECT n.*, 
                           e.numero_etudiant,
                           e.nom as nom_etudiant,
                           e.prenom as prenom_etudiant
                           FROM notes n 
                           JOIN etudiants e ON n.id_etudiant = e.id
                           WHERE n.id_unite_enseignement = ? 
                           AND n.type_session = ?
                           ORDER BY e.nom, e.prenom");
    $stmt->execute([$id_unite_enseignement, $type_session]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Création du classeur Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Informations de l'UE
    $sheet->setCellValue('A1', 'Unité d\'Enseignement :');
    $sheet->setCellValue('B1', $info['code'] . ' - ' . $info['intitule']);
    $sheet->setCellValue('A2', 'Session :');
    $sheet->setCellValue('B2', ucfirst($type_session));
    $sheet->setCellValue('A3', 'Date d\'export :');
    $sheet->setCellValue('B3', date('d/m/Y H:i'));

    // En-têtes des notes
    $sheet->setCellValue('A5', 'Numéro Étudiant');
    $sheet->setCellValue('B5', 'Nom');
    $sheet->setCellValue('C5', 'Prénom');
    $sheet->setCellValue('D5', 'Note');
    $sheet->setCellValue('E5', 'Date de Soumission');
    $sheet->setCellValue('F5', 'Statut');
    $sheet->setCellValue('G5', 'Commentaire');

    // Données
    $row = 6;
    foreach ($notes as $note) {
        $sheet->setCellValue('A' . $row, $note['numero_etudiant']);
        $sheet->setCellValue('B' . $row, $note['nom_etudiant']);
        $sheet->setCellValue('C' . $row, $note['prenom_etudiant']);
        $sheet->setCellValue('D' . $row, $note['note']);
        $sheet->setCellValue('E' . $row, date('d/m/Y H:i', strtotime($note['date_soumission'])));
        $sheet->setCellValue('F' . $row, ucfirst($note['statut']));
        $sheet->setCellValue('G' . $row, $note['commentaire']);
        $row++;
    }

    // Calcul des statistiques
    $lastRow = $row - 1;
    $row += 1;

    $sheet->setCellValue('A' . $row, 'Statistiques');
    $sheet->mergeCells('A' . $row . ':G' . $row);
    $row++;

    $sheet->setCellValue('A' . $row, 'Nombre d\'étudiants :');
    $sheet->setCellValue('B' . $row, '=COUNT(D6:D' . $lastRow . ')');
    $row++;

    $sheet->setCellValue('A' . $row, 'Moyenne :');
    $sheet->setCellValue('B' . $row, '=AVERAGE(D6:D' . $lastRow . ')');
    $row++;

    $sheet->setCellValue('A' . $row, 'Note minimale :');
    $sheet->setCellValue('B' . $row, '=MIN(D6:D' . $lastRow . ')');
    $row++;

    $sheet->setCellValue('A' . $row, 'Note maximale :');
    $sheet->setCellValue('B' . $row, '=MAX(D6:D' . $lastRow . ')');
    $row++;

    $sheet->setCellValue('A' . $row, 'Taux de réussite :');
    $sheet->setCellValue('B' . $row, '=COUNTIF(D6:D' . $lastRow . ', ">=10")/COUNT(D6:D' . $lastRow . ')');
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('0.00%');

    // Style des en-têtes
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'CCCCCC',
            ],
        ],
    ];
    $sheet->getStyle('A5:G5')->applyFromArray($headerStyle);
    $sheet->getStyle('A1:A3')->applyFromArray($headerStyle);

    // Ajustement automatique de la largeur des colonnes
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Création du fichier
    $writer = new Xlsx($spreadsheet);
    
    // Nom du fichier
    $filename = 'Notes_' . $info['code'] . '_' . $type_session . '_' . date('Y-m-d') . '.xlsx';

    // Headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Envoi du fichier
    ob_end_clean();
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "Une erreur est survenue lors du téléchargement : " . $e->getMessage();
    exit;
}
?> 