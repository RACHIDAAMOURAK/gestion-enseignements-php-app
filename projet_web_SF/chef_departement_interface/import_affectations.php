<?php
session_start();
include 'db.php';

// Vérifier si les dépendances composer sont installées
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("
        <h2>Erreur: Dépendances manquantes</h2>
        <p>Cette fonctionnalité nécessite l'installation de la bibliothèque PhpSpreadsheet via Composer.</p>
        <p>Pour installer les dépendances, suivez ces étapes:</p>
        <ol>
            <li>Installez Composer depuis <a href='https://getcomposer.org/download/' target='_blank'>getcomposer.org</a></li>
            <li>Ouvrez un terminal dans le répertoire racine du projet</li>
            <li>Exécutez la commande <code>composer install</code></li>
        </ol>
        <p><a href='javascript:history.back()'>Retour à la page précédente</a></p>
    ");
}

require __DIR__ . '/../vendor/autoload.php'; // Pour charger la bibliothèque PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Vérifier si l'utilisateur est connecté et a les droits
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'chef_departement') {
    $_SESSION['message'] = "Erreur: Vous n'avez pas les droits pour effectuer cette action.";
    $_SESSION['message_timestamp'] = time();
    header('Location: generations_rapports_decisions.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_affectations'])) {
    $pdo = connectDB();
    $id_departement = $_SESSION['id_departement'];
    $annee_universitaire = $_POST['annee_universitaire'];
    $semestre = $_POST['semestre'];
    
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Erreur: Problème lors de l'upload du fichier.";
        $_SESSION['message_timestamp'] = time();
        header('Location: generations_rapports_decisions.php');
        exit;
    }
    
    // Vérifier l'extension du fichier
    $fileType = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);
    if ($fileType != 'xlsx' && $fileType != 'xls') {
        $_SESSION['message'] = "Erreur: Seuls les fichiers Excel (.xlsx, .xls) sont acceptés.";
        $_SESSION['message_timestamp'] = time();
        header('Location: generations_rapports_decisions.php');
        exit;
    }
    
    try {
        // Charger le fichier Excel
        $inputFileName = $_FILES['excelFile']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        // Compteurs pour le rapport
        $importCount = 0;
        $errorCount = 0;
        $errors = [];
        
        // Parcourir les lignes du fichier Excel (en commençant par la ligne 2, supposant que la ligne 1 est l'en-tête)
        for ($row = 2; $row <= $highestRow; $row++) {
            // Lire les données
            $enseignantNomComplet = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());
            $ueComplet = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue());
            $filiere = trim($worksheet->getCellByColumnAndRow(3, $row)->getValue());
            $typeCours = trim($worksheet->getCellByColumnAndRow(4, $row)->getValue());
            $volumeHoraire = (int)$worksheet->getCellByColumnAndRow(5, $row)->getValue();
            
            // Vérifier les données obligatoires
            if (empty($enseignantNomComplet) || empty($ueComplet) || empty($filiere) || empty($typeCours) || $volumeHoraire <= 0) {
                $errors[] = "Ligne $row: Données incomplètes ou incorrectes";
                $errorCount++;
                continue;
            }
            
            // Extraire le nom et prénom de l'enseignant
            $enseignantParts = explode(' ', $enseignantNomComplet, 2);
            if (count($enseignantParts) < 2) {
                $errors[] = "Ligne $row: Format du nom de l'enseignant incorrect (doit être 'Nom Prénom')";
                $errorCount++;
                continue;
            }
            $nomEnseignant = $enseignantParts[0];
            $prenomEnseignant = $enseignantParts[1];
            
            // Extraire le code UE et l'intitulé
            $ueParts = explode(' - ', $ueComplet, 2);
            if (count($ueParts) < 2) {
                $errors[] = "Ligne $row: Format de l'UE incorrect (doit être 'Code - Intitulé')";
                $errorCount++;
                continue;
            }
            $codeUE = $ueParts[0];
            $intituleUE = $ueParts[1];
            
            // Vérifier que le type de cours est valide
            if (!in_array($typeCours, ['CM', 'TD', 'TP'])) {
                $errors[] = "Ligne $row: Type de cours invalide (doit être CM, TD ou TP)";
                $errorCount++;
                continue;
            }
            
            // Récupérer l'ID de l'enseignant
            $stmt = $pdo->prepare("
                SELECT id FROM utilisateurs 
                WHERE nom = ? AND prenom = ? AND id_departement = ?
            ");
            $stmt->execute([$nomEnseignant, $prenomEnseignant, $id_departement]);
            $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$enseignant) {
                $errors[] = "Ligne $row: Enseignant '$enseignantNomComplet' non trouvé dans ce département";
                $errorCount++;
                continue;
            }
            
            // Récupérer l'ID de l'UE
            $stmt = $pdo->prepare("
                SELECT id FROM unites_enseignement 
                WHERE code = ? AND intitule = ?
            ");
            $stmt->execute([$codeUE, $intituleUE]);
            $ue = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ue) {
                $errors[] = "Ligne $row: UE '$codeUE - $intituleUE' non trouvée";
                $errorCount++;
                continue;
            }
            
            // Récupérer l'ID de la filière
            $stmt = $pdo->prepare("
                SELECT id FROM filieres 
                WHERE nom = ?
            ");
            $stmt->execute([$filiere]);
            $filiereData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$filiereData) {
                $errors[] = "Ligne $row: Filière '$filiere' non trouvée";
                $errorCount++;
                continue;
            }
            
            // Vérifier si cette affectation existe déjà
            $stmt = $pdo->prepare("
                SELECT id FROM historique_affectations
                WHERE id_utilisateur = ? AND id_unite_enseignement = ? AND id_filiere = ?
                AND type_cours = ? AND annee_universitaire = ? AND semestre = ?
            ");
            $stmt->execute([
                $enseignant['id'], 
                $ue['id'], 
                $filiereData['id'], 
                $typeCours, 
                $annee_universitaire, 
                $semestre
            ]);
            
            if ($stmt->rowCount() > 0) {
                // Mise à jour de l'affectation existante
                $stmt = $pdo->prepare("
                    UPDATE historique_affectations
                    SET volume_horaire = ?, date_affectation = NOW()
                    WHERE id_utilisateur = ? AND id_unite_enseignement = ? AND id_filiere = ?
                    AND type_cours = ? AND annee_universitaire = ? AND semestre = ?
                ");
                $stmt->execute([
                    $volumeHoraire,
                    $enseignant['id'], 
                    $ue['id'], 
                    $filiereData['id'], 
                    $typeCours, 
                    $annee_universitaire, 
                    $semestre
                ]);
            } else {
                // Nouvelle affectation
                $stmt = $pdo->prepare("
                    INSERT INTO historique_affectations
                    (id_utilisateur, id_unite_enseignement, id_filiere, id_departement, 
                    type_cours, volume_horaire, annee_universitaire, semestre, date_affectation)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $enseignant['id'], 
                    $ue['id'], 
                    $filiereData['id'], 
                    $id_departement,
                    $typeCours, 
                    $volumeHoraire, 
                    $annee_universitaire, 
                    $semestre
                ]);
            }
            
            $importCount++;
        }
        
        // Message de résultat
        if ($importCount > 0) {
            $_SESSION['message'] = "Importation réussie: $importCount affectation(s) importée(s)" . 
                                  ($errorCount > 0 ? ", $errorCount erreur(s)" : "");
        } else {
            $_SESSION['message'] = "Aucune affectation n'a été importée. $errorCount erreur(s) rencontrée(s).";
        }
        
        // Stocker les erreurs détaillées dans la session
        if ($errorCount > 0) {
            $_SESSION['import_errors'] = $errors;
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Erreur lors de l'importation: " . $e->getMessage();
    }
    
    $_SESSION['message_timestamp'] = time();
    header('Location: generations_rapports_decisions.php');
    exit;
}

// Si on arrive ici, c'est qu'il y a eu une erreur dans la soumission du formulaire
$_SESSION['message'] = "Erreur: Formulaire d'importation invalide.";
$_SESSION['message_timestamp'] = time();
header('Location: generations_rapports_decisions.php');
exit;
?>