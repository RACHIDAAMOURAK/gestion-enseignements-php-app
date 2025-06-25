<?php
require_once '../includes/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

    // Récupération des séances
    $query_seances = "SELECT DISTINCT s.*, 
                        ue.code as code_ue, 
                        ue.intitule as intitule_ue,
                        CONCAT(g.type, ' ', g.numero) as nom_groupe,
                        s.salle,
                        f.nom as nom_filiere,
                        et.semestre
                    FROM utilisateurs u
                    JOIN historique_affectations ha ON u.id = ha.id_utilisateur
                    JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
                    JOIN seances s ON ue.id = s.id_unite_enseignement AND s.type = ha.type_cours
                    JOIN emplois_temps et ON s.id_emploi_temps = et.id
                    JOIN filieres f ON et.id_filiere = f.id
                    LEFT JOIN groupes g ON s.id_groupe = g.id
                    WHERE u.id = ? 
                    AND et.statut = 'actif'
                    GROUP BY s.id
                    ORDER BY s.jour, s.heure_debut";

    $stmt = $conn->prepare($query_seances);
    $stmt->bind_param("i", $id_enseignant);
    $stmt->execute();
    $result_seances = $stmt->get_result();

    // Organiser les séances par jour et heure
    $seances_par_jour = [];
    while ($seance = $result_seances->fetch_assoc()) {
        $jour = $seance['jour'];
        $heure = substr($seance['heure_debut'], 0, 5);
        
        if (!isset($seances_par_jour[$jour])) {
            $seances_par_jour[$jour] = [];
        }
        if (!isset($seances_par_jour[$jour][$heure])) {
            $seances_par_jour[$jour][$heure] = [];
        }

        // Vérifier si la séance n'existe pas déjà
        $existe = false;
        foreach ($seances_par_jour[$jour][$heure] as $s) {
            if ($s['id'] === $seance['id']) {
                $existe = true;
                break;
            }
        }
        
        if (!$existe) {
            $seances_par_jour[$jour][$heure][] = $seance;
        }
    }

    // Création du contenu HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Emploi du temps</title>
        <style>
            @page {
                size: landscape;
                margin: 10mm;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 10pt;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #000;
                padding: 4px;
                text-align: left;
                font-size: 9pt;
            }
            th {
                background-color: #f0f0f0;
            }
            .seance {
                margin-bottom: 3px;
            }
            .seance-header {
                font-weight: bold;
            }
            h1 {
                text-align: center;
                color: #000;
                font-size: 14pt;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
    ';

    $html .= '<h1>Emploi du temps - ' . htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']) . '</h1>';

    $html .= '<table>';
    $html .= '<tr><th>Horaire</th><th>Lundi</th><th>Mardi</th><th>Mercredi</th><th>Jeudi</th><th>Vendredi</th></tr>';

    $horaires = ['08:00', '10:00', '14:00', '16:00'];
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];

    foreach ($horaires as $heure) {
        $heure_fin = date('H:i', strtotime($heure . ' +2 hours'));
        $html .= '<tr>';
        $html .= '<td>' . $heure . ' - ' . $heure_fin . '</td>';

        foreach ($jours as $jour) {
            $html .= '<td>';
            if (isset($seances_par_jour[$jour][$heure])) {
                foreach ($seances_par_jour[$jour][$heure] as $seance) {
                    $html .= '<div class="seance">';
                    $html .= '<div class="seance-header">' . htmlspecialchars($seance['intitule_ue']) . '</div>';
                    $html .= htmlspecialchars($seance['code_ue']) . ' - ' . htmlspecialchars($seance['type']);
                    if ($seance['type'] !== 'CM') {
                        $html .= ' ' . htmlspecialchars($seance['nom_groupe']);
                    }
                    $html .= '<br>';
                    $html .= htmlspecialchars($seance['nom_filiere']) . ' S' . htmlspecialchars($seance['semestre']) . '<br>';
                    $html .= 'Salle: ' . htmlspecialchars($seance['salle']);
                    $html .= '</div>';
                }
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    $html .= '</body></html>';

    // Configuration de Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    // Nom du fichier
    $filename = 'EDT_' . $enseignant['nom'] . '.pdf';
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

    // Envoi du PDF
    $dompdf->stream($filename, array('Attachment' => true));
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "Une erreur est survenue lors de l'export : " . $e->getMessage();
    exit;
} 