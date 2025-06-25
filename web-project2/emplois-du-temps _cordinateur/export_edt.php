<?php
require_once "includes/db.php";

// Récupération des paramètres
$id_enseignant = isset($_GET['id_enseignant']) ? intval($_GET['id_enseignant']) : 1; // Valeur par défaut 1
$semaine = isset($_GET['semaine']) ? $_GET['semaine'] : date('Y-m-d', strtotime('monday this week'));

// Récupération des informations de l'enseignant
$query_enseignant = "SELECT nom, prenom FROM utilisateurs WHERE id = ?";
$stmt_enseignant = mysqli_prepare($conn, $query_enseignant);
mysqli_stmt_bind_param($stmt_enseignant, "i", $id_enseignant);
mysqli_stmt_execute($stmt_enseignant);
$result_enseignant = mysqli_stmt_get_result($stmt_enseignant);
$enseignant = mysqli_fetch_assoc($result_enseignant);

// Calcul des dates de la semaine
$date_debut = new DateTime($semaine);
$dates_semaine = [];
for ($i = 0; $i < 6; $i++) {
    $date_courante = clone $date_debut;
    $date_courante->modify("+$i day");
    $dates_semaine[] = $date_courante;
}

// Récupération des séances
$query_seances = "SELECT s.*, 
                       ue.code as code_ue, ue.intitule as intitule_ue,
                       CONCAT(g.type, ' ', g.numero) as nom_groupe,
                       f.nom as nom_filiere
                FROM seances s
                JOIN unites_enseignement ue ON s.id_unite_enseignement = ue.id
                JOIN groupes g ON s.id_groupe = g.id
                JOIN filieres f ON ue.id_filiere = f.id
                WHERE s.jour BETWEEN ? AND ?
                AND s.id_enseignant = ?
                ORDER BY s.jour, s.heure_debut";

$params = [$date_debut->format('Y-m-d'), 
         $date_debut->modify('+5 days')->format('Y-m-d'),
         $id_enseignant];
$types = "ssi";

$stmt = mysqli_prepare($conn, $query_seances);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_seances = mysqli_stmt_get_result($stmt);

$seances_par_jour = [];
while ($seance = mysqli_fetch_assoc($result_seances)) {
    $jour = $seance['jour'];
    if (!isset($seances_par_jour[$jour])) {
        $seances_par_jour[$jour] = [];
    }
    $seances_par_jour[$jour][] = $seance;
}

// Création du PDF
require_once 'vendor/autoload.php';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuration du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Système de Gestion');
$pdf->SetTitle('Emploi du temps - ' . $enseignant['nom'] . ' ' . $enseignant['prenom']);

// Configuration des marges
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configuration des polices
$pdf->SetFont('helvetica', '', 10);

// Ajout d'une page
$pdf->AddPage('L'); // Format paysage pour plus d'espace

// Titre
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Emploi du temps', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, $enseignant['nom'] . ' ' . $enseignant['prenom'], 0, 1, 'C');
$pdf->Cell(0, 10, 'Semaine du ' . $date_debut->format('d/m/Y'), 0, 1, 'C');
$pdf->Ln(10);

// Tableau des séances
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(240, 240, 240);

// En-tête du tableau
$pdf->Cell(20, 7, 'Horaire', 1, 0, 'C', 1);
foreach ($dates_semaine as $date) {
    $pdf->Cell(43, 7, $date->format('D d/m'), 1, 0, 'C', 1);
}
$pdf->Ln();

// Corps du tableau
$heures = ['08:00', '09:30', '11:00', '14:00', '15:30', '17:00'];
foreach ($heures as $heure) {
    $pdf->Cell(20, 30, $heure, 1, 0, 'C');
    
    foreach ($dates_semaine as $date) {
        $jour = $date->format('Y-m-d');
        $cell_content = '';
        
        if (isset($seances_par_jour[$jour])) {
            foreach ($seances_par_jour[$jour] as $seance) {
                if ($seance['heure_debut'] === $heure . ':00') {
                    $cell_content .= $seance['code_ue'] . "\n";
                    $cell_content .= $seance['type'] . ' - ' . $seance['nom_groupe'] . "\n";
                    $cell_content .= 'Salle: ' . $seance['salle'] . "\n";
                    $cell_content .= $seance['nom_filiere'];
                }
            }
        }
        
        $pdf->MultiCell(43, 30, $cell_content, 1, 'C');
        $pdf->SetY($pdf->GetY() - 30);
        $pdf->SetX($pdf->GetX() + 43);
    }
    
    $pdf->Ln(30);
}

// Génération du PDF
$pdf->Output('emploi_du_temps.pdf', 'D'); 