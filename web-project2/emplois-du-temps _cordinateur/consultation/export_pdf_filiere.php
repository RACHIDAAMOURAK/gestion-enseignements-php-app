<?php
require_once "../includes/config.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    if (!isset($_GET['id_filiere']) || !isset($_GET['semestre']) || !isset($_GET['annee'])) {
        throw new Exception("Paramètres manquants");
    }

    $id_filiere = intval($_GET['id_filiere']);
    $semestre = intval($_GET['semestre']);
    $annee = $_GET['annee'];

    // Récupérer les informations de la filière
    $stmt = $conn->prepare("SELECT nom FROM filieres WHERE id = ?");
    $stmt->bind_param("i", $id_filiere);
    $stmt->execute();
    $result = $stmt->get_result();
    $filiere = $result->fetch_assoc();

    if (!$filiere) {
        throw new Exception("Filière non trouvée");
    }

    // Récupération des séances
    $query_seances = "SELECT DISTINCT s.*, 
                        ue.code as code_ue, 
                        ue.intitule as intitule_ue,
                        CONCAT(g.type, ' ', g.numero) as nom_groupe,
                        s.salle,
                        COALESCE(ha.nom_utilisateur, '??') as nom_enseignant,
                        s.type as type_cours
                    FROM seances s
                    INNER JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
                    LEFT JOIN historique_affectations ha ON (ue.id = ha.id_unite_enseignement AND s.type = ha.type_cours)
                    LEFT JOIN groupes g ON s.id_groupe = g.id
                    WHERE s.id_emploi_temps IN (
                        SELECT id FROM emplois_temps 
                        WHERE id_filiere = ? AND semestre = ? AND annee_universitaire = ? AND statut = 'actif'
                    )
                    ORDER BY s.jour, s.heure_debut";

    $stmt = $conn->prepare($query_seances);
    $stmt->bind_param("iis", $id_filiere, $semestre, $annee);
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
            .subtitle {
                text-align: center;
                color: #666;
                font-size: 12pt;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
    ';

    $html .= '<h1>Emploi du temps - ' . htmlspecialchars($filiere['nom']) . '</h1>';
    $html .= '<div class="subtitle">Semestre ' . $semestre . ' - Année universitaire ' . $annee . '</div>';

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
                    $html .= htmlspecialchars($seance['code_ue']) . ' - ' . htmlspecialchars($seance['type_cours']);
                    if ($seance['type_cours'] !== 'CM') {
                        $html .= ' ' . htmlspecialchars($seance['nom_groupe']);
                    }
                    $html .= '<br>';
                    $html .= 'Prof: ' . htmlspecialchars($seance['nom_enseignant']) . '<br>';
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
    $filename = 'EDT_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filiere['nom']) . '_S' . $semestre . '_' . $annee . '.pdf';

    // Envoi du PDF
    $dompdf->stream($filename, array('Attachment' => true));
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "Une erreur est survenue lors de l'export : " . $e->getMessage();
    exit;
} 