<?php
session_start(); 
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et vacataire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vacataire') {
    header('Location: ../login.php');
    exit();
}

// Vérifier les paramètres
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    die('Paramètres manquants');
}

$file_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$type = $_GET['type'];

if ($file_id === false || !in_array($type, ['excel', 'pdf'])) {
    die('Paramètres invalides');
}

try {
    // Récupérer les informations du fichier
    $stmt = $pdo->prepare("
        SELECT fn.*, ue.intitule as module_nom, ue.code as module_code
        FROM fichiers_notes fn
        JOIN unites_enseignement ue ON fn.id_unite_enseignement = ue.id
        WHERE fn.id = ? AND fn.id_enseignant = ?
    ");
    $stmt->execute([$file_id, $_SESSION['user_id']]);
    $file = $stmt->fetch();

    if (!$file) {
        die('Fichier non trouvé ou accès non autorisé');
    }

    $file_path = '../uploads/notes/' . $file['chemin_fichier'];
    if (!file_exists($file_path)) {
        die('Le fichier n\'existe plus sur le serveur');
    }

    // Définir les en-têtes selon le type de fichier
    if ($type === 'excel') {
        // Déterminer le type MIME correct en fonction de l'extension
        $extension = strtolower(pathinfo($file['nom_fichier'], PATHINFO_EXTENSION));
        $mime_type = match($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };

        // Nettoyer le buffer de sortie
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Définir les en-têtes
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $file['nom_fichier'] . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Lire et envoyer le fichier
        $handle = fopen($file_path, 'rb');
        if ($handle === false) {
            throw new Exception('Impossible d\'ouvrir le fichier');
        }

        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        exit;

    } else {
        // Pour le PDF, on va d'abord convertir le fichier Excel en PDF
        require_once 'vendor/autoload.php';

        // Lire le fichier Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();

        // Créer un nouveau PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Définir les informations du document
        $pdf->SetCreator('Système de Gestion des Notes');
        $pdf->SetAuthor($_SESSION['user_name']);
        $pdf->SetTitle($file['module_nom'] . ' - ' . $file['type_session']);

        // Ajouter une page
        $pdf->AddPage();

        // Ajouter le contenu
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Module: ' . $file['module_nom'] . ' (' . $file['module_code'] . ')', 0, 1);
        $pdf->Cell(0, 10, 'Session: ' . ucfirst($file['type_session']), 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . date('d/m/Y H:i', strtotime($file['date_upload'])), 0, 1);
        $pdf->Ln(10);

        // Convertir les données Excel en tableau HTML
        $html = '<table border="1" cellpadding="3">';
        foreach ($worksheet->getRowIterator() as $row) {
            $html .= '<tr>';
            foreach ($row->getCellIterator() as $cell) {
                $html .= '<td>' . $cell->getValue() . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Ajouter le tableau au PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Générer le PDF
        $pdf_content = $pdf->Output('', 'S');

        // Nettoyer le buffer de sortie
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Envoyer le PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . pathinfo($file['nom_fichier'], PATHINFO_FILENAME) . '.pdf"');
        header('Content-Length: ' . strlen($pdf_content));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $pdf_content;
        exit;
    }

} catch (Exception $e) {
    die('Erreur lors du téléchargement: ' . $e->getMessage());
} 