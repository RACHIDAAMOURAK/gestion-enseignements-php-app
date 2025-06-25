<?php
session_start();
include 'db.php';
$pdo = connectDB();

// Fonction de redirection sécurisée
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit;
    }
}

// Fonction de nettoyage des chaînes
function cleanString($value) {
    // 1. Supprimer les guillemets et caractères spéciaux
    $value = str_replace(['"', "'", "\\"], '', $value);
    
    // 2. Remplacer les espaces insécables et autres whitespaces
    $value = preg_replace('/\s+/u', ' ', $value);
    
    // 3. Trim + lowercase
    return mb_strtolower(trim($value), 'UTF-8');
}

// Traitement de l'import Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importer_excel'])) {
    if (isset($_FILES['fichier_excel']) && $_FILES['fichier_excel']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fichier_excel']['tmp_name'];
        $fileType = pathinfo($_FILES['fichier_excel']['name'], PATHINFO_EXTENSION);
        
        // Log pour déboguer
        error_log("Traitement du fichier Excel: " . $_FILES['fichier_excel']['name']);
        
        // Vérifier que c'est bien un fichier Excel
        if ($fileType !== 'xlsx' && $fileType !== 'xls') {
            $_SESSION['message'] = "Erreur: Seuls les fichiers Excel (.xlsx, .xls) sont acceptés";
            $_SESSION['message_timestamp'] = time();
            redirect($_SERVER['PHP_SELF']);
        }
        
        // Lire le fichier Excel
        $zip = new ZipArchive;
        if ($zip->open($file) === TRUE) {
            error_log("Fichier Excel ouvert avec succès");
            
            // Lire les chaînes partagées
            $sharedStrings = [];
            if (($sharedStringsIndex = $zip->locateName('xl/sharedStrings.xml')) !== false) {
                $sharedStringsContent = $zip->getFromIndex($sharedStringsIndex);
                $xml = simplexml_load_string($sharedStringsContent);
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
                error_log("Nombre de chaînes partagées lues: " . count($sharedStrings));
            } else {
                error_log("Aucune chaîne partagée trouvée dans le fichier");
            }
            
            // Trouver la feuille de calcul
            $worksheetFound = false;
            $worksheetContent = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (strpos($filename, 'xl/worksheets/sheet') !== false) {
                    $worksheetContent = $zip->getFromIndex($i);
                    $worksheetFound = true;
                    error_log("Feuille de calcul trouvée: " . $filename);
                    break;
                }
            }
            
            if (!$worksheetFound) {
                error_log("Aucune feuille de calcul trouvée dans le fichier");
                $_SESSION['message'] = "Erreur: Impossible de trouver la feuille de calcul";
                $_SESSION['message_timestamp'] = time();
                redirect($_SERVER['PHP_SELF']);
            }
            
            $zip->close();
            
            // Parser le contenu
            $xml = simplexml_load_string($worksheetContent);
            $rows = $xml->sheetData->row;
            
            $importedData = [];
            foreach ($rows as $row) {
                $rowData = [];
                foreach ($row->c as $cell) {
                    $value = '';
                    $cellAttributes = $cell->attributes();
                    $cellRef = (string)$cellAttributes['r'];
                    
                    $columnLetter = preg_replace('/[0-9]/', '', $cellRef);
                    $colIndex = 0;
                    $len = strlen($columnLetter);
                    for ($i = 0; $i < $len; $i++) {
                        $colIndex = $colIndex * 26 + (ord($columnLetter[$i]) - ord('A') + 1);
                    }
                    $colIndex--;
                    
                    if (isset($cell->v)) {
                        if (isset($cellAttributes['t']) && $cellAttributes['t'] == 's') {
                            $index = (int)$cell->v;
                            $value = $sharedStrings[$index] ?? '';
                        } else {
                            $value = (string)$cell->v;
                        }
                    }
                    
                    $rowData[$colIndex] = $value;
                }
                
                // Remplir les colonnes manquantes
                for ($i = 0; $i <= 5; $i++) {
                    if (!isset($rowData[$i])) {
                        $rowData[$i] = '';
                    }
                }
                
                ksort($rowData);
                $importedData[] = array_values($rowData);
            }
            
            error_log("Nombre de lignes lues: " . count($importedData));
            
            // Supprimer l'en-tête
            if (count($importedData) > 0) {
                array_shift($importedData);
                error_log("En-tête supprimé, nombre de lignes restantes: " . count($importedData));
            }
            
            // Traitement des données
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $anneeUniversitaire = date('Y') . '-' . (date('Y') + 1);
            $semestre = 1;
            
            // Nom du département
            $stmt = $pdo->prepare("SELECT nom FROM departements WHERE id = ?");
            $stmt->execute([$_SESSION['id_departement']]);
            $nom_departement = $stmt->fetchColumn() ?: 'Département ' . $_SESSION['id_departement'];
            
            foreach ($importedData as $rowIndex => $row) {
                error_log("Traitement de la ligne " . ($rowIndex + 1) . ": " . implode(" | ", $row));
                
                if (count($row) < 6 || empty($row[0]) || empty($row[1]) || empty($row[2])) {
                    $errorCount++;
                    $errors[] = "Ligne incomplète: " . implode(" | ", $row);
                    error_log("Ligne incomplète détectée");
                    continue;
                }
                
                try {
                    // 1. Traitement de la date
                    $dateFr = trim($row[0]);
                    $dateObj = DateTime::createFromFormat('d/m/Y H:i', $dateFr) ?: DateTime::createFromFormat('d/m/Y', $dateFr);
                    if (!$dateObj) {
                        throw new Exception("Format de date invalide: " . $dateFr);
                    }
                    $dateSql = $dateObj->format('Y-m-d H:i:s');
                    
                    // 2. Traitement UE
                    $ueFull = trim($row[2]);
                    $ueParts = explode(' - ', $ueFull, 2);
                    $codeUE = trim($ueParts[0]);
                    $nomUE = $ueParts[1] ?? '';
                    
                    // 3. Nettoyage des données
                    $enseignant = cleanString($row[1]);
                    $filiere = cleanString($row[3]);
                    $type = cleanString($row[4]);
                    $volume = trim($row[5]);
                    
                    // Validation
                    if (empty($enseignant) || empty($codeUE) || empty($filiere) || empty($type)) {
                        throw new Exception("Champs obligatoires manquants");
                    }
                    
                    if (!is_numeric($volume)) {
                        throw new Exception("Volume horaire invalide: " . $volume);
                    }
                    
                    // Recherche enseignant
                    $stmt = $pdo->prepare("SELECT id, CONCAT(nom, ' ', prenom) AS nom_complet, role 
                                           FROM utilisateurs 
                                           WHERE LOWER(CONCAT(nom, ' ', prenom)) = ? 
                                           OR LOWER(CONCAT(prenom, ' ', nom)) = ?");
                    $stmt->execute([$enseignant, $enseignant]);
                    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$utilisateur) {
                        throw new Exception("Enseignant non trouvé: " . $enseignant);
                    }
                    
                    // Recherche UE
                    $stmt = $pdo->prepare("SELECT id FROM unites_enseignement WHERE code = ?");
                    $stmt->execute([$codeUE]);
                    $id_unite_enseignement = $stmt->fetchColumn();
                    
                    if (!$id_unite_enseignement) {
                        $stmt = $pdo->prepare("INSERT INTO unites_enseignement (code, intitule, id_departement) VALUES (?, ?, ?)");
                        $stmt->execute([$codeUE, $nomUE, $_SESSION['id_departement']]);
                        $id_unite_enseignement = $pdo->lastInsertId();
                    }
                    
                    // RECHERCHE FILIÈRE
                    $stmt = $pdo->prepare("SELECT id, nom FROM filieres WHERE LOWER(TRIM(nom)) = ?");
                    $stmt->execute([$filiere]);
                    $filiere_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$filiere_data) {
                        // Créer la filière si elle n'existe pas
                        $stmt = $pdo->prepare("INSERT INTO filieres (nom, id_departement) VALUES (?, ?)");
                        $stmt->execute([ucfirst(trim($filiere)), $_SESSION['id_departement']]);
                        $filiere_data = [
                            'id' => $pdo->lastInsertId(),
                            'nom' => ucfirst(trim($filiere))
                        ];
                    }
                    
                    // Dans la requête de recherche de l'utilisateur, on récupère bien son rôle réel
                    $stmt = $pdo->prepare("SELECT id, CONCAT(nom, ' ', prenom) AS nom_complet, role 
                    FROM utilisateurs 
                    WHERE LOWER(CONCAT(nom, ' ', prenom)) = ? 
                    OR LOWER(CONCAT(prenom, ' ', nom)) = ?");
                    $stmt->execute([$enseignant, $enseignant]);
                    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$utilisateur) {
                        throw new Exception("Enseignant non trouvé: " . $enseignant);
                    }

                    // On utilise le rôle réel de l'utilisateur sans valeur par défaut
                    $role_utilisateur = $utilisateur['role'];

                    // Insertion dans historique_affectations avec le vrai rôle
                    $stmt = $pdo->prepare("INSERT INTO historique_affectations 
                    (id_utilisateur, nom_utilisateur, role,
                    id_unite_enseignement, code_ue, intitule_ue, 
                    id_filiere, nom_filiere, id_departement, nom_departement,
                    type_cours, volume_horaire, annee_universitaire, semestre, 
                    statut, date_affectation, commentaire_chef)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $result = $stmt->execute([
                        $utilisateur['id'],
                        $utilisateur['nom_complet'],
                        $role_utilisateur,
                        $id_unite_enseignement,
                        $codeUE,
                        $nomUE,
                        $filiere_data['id'],
                        $filiere_data['nom'],
                        $_SESSION['id_departement'],
                        $nom_departement,
                        $type,
                        $volume,
                        $anneeUniversitaire,
                        $semestre,
                        'validé',
                        $dateSql,
                        'Importé depuis Excel'
                    ]);
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        throw new Exception("Erreur d'insertion: " . $errorInfo[2]);
                    }
                    
                    $successCount++;
                    error_log("Ligne traitée avec succès");
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Erreur ligne: " . implode(" | ", $row) . " → " . $e->getMessage();
                    error_log("Erreur lors du traitement de la ligne: " . $e->getMessage());
                }
            }
            
            // Message de résultat
            if ($errorCount > 0) {
                $_SESSION['message'] = "L'importation n'a pas pu être effectuée. Veuillez vérifier que votre fichier Excel contient toutes les colonnes requises (Date, Enseignant, UE, Filière, Type, Volume Horaire) et que les données sont correctement formatées.";
            } else {
                $_SESSION['message'] = "Import terminé avec succès : $successCount ligne(s) importée(s)";
            }
            $_SESSION['message_timestamp'] = time();
            error_log("Import terminé: $successCount succès, $errorCount erreurs");
        } else {
            error_log("Impossible d'ouvrir le fichier Excel");
            $_SESSION['message'] = "Erreur: Impossible d'ouvrir le fichier Excel";
            $_SESSION['message_timestamp'] = time();
        }
    } else {
        error_log("Aucun fichier valide téléchargé ou erreur lors du téléchargement");
        $_SESSION['message'] = "Erreur: Aucun fichier valide téléchargé";
        $_SESSION['message_timestamp'] = time();
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Inclure le header après le traitement de l'import
include 'header.php';

// Le reste du code reste inchangé...
// Générer le rapport si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generer_rapport'])) {
    // Vérifier si c'est une année personnalisée
    if ($_POST['annee_universitaire'] === 'custom' && isset($_POST['annee_custom']) && !empty($_POST['annee_custom'])) {
        $annee_universitaire = htmlspecialchars($_POST['annee_custom']);
        // Vérifier le format AAAA-AAAA
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_universitaire)) {
            $_SESSION['message'] = "Erreur: Format d'année incorrect. Utilisez le format AAAA-AAAA";
            $_SESSION['message_timestamp'] = time();
            redirect($_SERVER['PHP_SELF']);
        }
    } else {
        $annee_universitaire = $_POST['annee_universitaire'];
    }
    
    $semestre = $_POST['semestre'];
    $id_departement = $_SESSION['id_departement'];
    
    // Calcul des totaux pour CM, TD, TP
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN type_cours = 'CM' THEN volume_horaire ELSE 0 END) as total_heures_cm,
            SUM(CASE WHEN type_cours = 'TD' THEN volume_horaire ELSE 0 END) as total_heures_td,
            SUM(CASE WHEN type_cours = 'TP' THEN volume_horaire ELSE 0 END) as total_heures_tp,
            SUM(volume_horaire) as total_heures
        FROM historique_affectations
        WHERE id_departement = ? AND annee_universitaire = ? AND semestre = ?
    ");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $totaux = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nombre d'enseignants
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id_utilisateur) as nombre_enseignants
        FROM historique_affectations
        WHERE id_departement = ? AND annee_universitaire = ? AND semestre = ?
    ");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $nombre_enseignants = $stmt->fetchColumn();
    
    // Nombre de vacataires (UE déclarées vacantes)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id_unite_enseignement, id_filiere) as nombre_vacataires
        FROM unites_enseignement_vacantes
        WHERE id_departement = ? AND annee_universitaire = ? AND semestre = ?
    ");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $nombre_vacataires = $stmt->fetchColumn();
    
    // Récupérer les données historiques pour les graphiques
    $stmt = $pdo->prepare("
        SELECT 
            annee_universitaire,
            semestre,
            SUM(volume_horaire) as total_heures,
            COUNT(DISTINCT id_utilisateur) as nb_enseignants
        FROM historique_affectations
        WHERE id_departement = ?
        GROUP BY annee_universitaire, semestre
        ORDER BY annee_universitaire DESC, semestre
        LIMIT 5
    ");
    $stmt->execute([$id_departement]);
    $historique_charges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la répartition par filière
    $stmt = $pdo->prepare("
        SELECT 
            f.nom as filiere,
            COUNT(DISTINCT uev.id_unite_enseignement) as nb_ue_vacantes,
            SUM(uev.volume_horaire) as total_heures_vacantes
        FROM unites_enseignement_vacantes uev
        JOIN filieres f ON uev.id_filiere = f.id
        WHERE uev.id_departement = ? AND uev.annee_universitaire = ? AND uev.semestre = ?
        GROUP BY f.nom
    ");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $repartition_filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier si un rapport existe déjà pour cette période
    $stmt = $pdo->prepare("
        SELECT id FROM rapport_charge_departement
        WHERE id_departement = ? AND annee_universitaire = ? AND semestre = ?");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $rapport_existant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rapport_existant) {
        // Mettre à jour le rapport existant
        $stmt = $pdo->prepare("
            UPDATE rapport_charge_departement
            SET 
                total_heures_cm = ?,
                total_heures_td = ?,
                total_heures_tp = ?,
                total_heures = ?,
                nombre_enseignants = ?,
                nombre_vacataires = ?,
                date_generation = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $totaux['total_heures_cm'], 
            $totaux['total_heures_td'], 
            $totaux['total_heures_tp'], 
            $totaux['total_heures'], 
            $nombre_enseignants, 
            $nombre_vacataires,
            $rapport_existant['id']
        ]);
        $_SESSION['message'] = "Le rapport a été mis à jour avec succès";
        $_SESSION['message_timestamp'] = time();
    } else {
        // Créer un nouveau rapport
        $stmt = $pdo->prepare("
            INSERT INTO rapport_charge_departement
            (id_departement, annee_universitaire, semestre, total_heures_cm, total_heures_td, 
             total_heures_tp, total_heures, nombre_enseignants, nombre_vacataires, date_generation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $id_departement, 
            $annee_universitaire, 
            $semestre, 
            $totaux['total_heures_cm'], 
            $totaux['total_heures_td'], 
            $totaux['total_heures_tp'], 
            $totaux['total_heures'], 
            $nombre_enseignants, 
            $nombre_vacataires]
        );
        $_SESSION['message'] = "Le rapport a été généré avec succès";
        $_SESSION['message_timestamp'] = time();
    }
    
    // Récupérer le rapport généré
    $stmt = $pdo->prepare("
        SELECT * FROM rapport_charge_departement
        WHERE id_departement = ? AND annee_universitaire = ? AND semestre = ?
    ");
    $stmt->execute([$id_departement, $annee_universitaire, $semestre]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer détails des affectations pour le rapport
    $stmt = $pdo->prepare("
        SELECT 
            u.nom, u.prenom, u.role,
            SUM(CASE WHEN ha.type_cours = 'CM' THEN ha.volume_horaire ELSE 0 END) as heures_cm,
            SUM(CASE WHEN ha.type_cours = 'TD' THEN ha.volume_horaire ELSE 0 END) as heures_td,
            SUM(CASE WHEN ha.type_cours = 'TP' THEN ha.volume_horaire ELSE 0 END) as heures_tp,
            SUM(ha.volume_horaire) as total_heures
        FROM utilisateurs u
        JOIN historique_affectations ha ON u.id = ha.id_utilisateur
        WHERE ha.id_departement = ? 
        AND ha.semestre = ?
        AND (
            (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) >= 9)  -- Affectations de septembre à décembre
            OR 
            (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) <= 8)  -- Affectations de janvier à août
        )
        GROUP BY u.id
        ORDER BY u.nom, u.prenom
    ");
    // Extraire les années de début et de fin
    $annee_debut = substr($annee_universitaire, 0, 4);
    $annee_fin = substr($annee_universitaire, 5, 4);
    $stmt->execute([$id_departement, $semestre, $annee_debut, $annee_fin]);
    $details_enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer détails des UE vacantes avec filière
    $stmt = $pdo->prepare("
        SELECT 
            ue.code, ue.intitule, 
            f.nom as nom_filiere,
            uev.type_cours, uev.volume_horaire,
            uev.date_declaration
        FROM unites_enseignement_vacantes uev
        JOIN unites_enseignement ue ON uev.id_unite_enseignement = ue.id
        JOIN filieres f ON uev.id_filiere = f.id
        WHERE uev.id_departement = ? 
        AND uev.semestre = ?
        AND (
            (YEAR(uev.date_declaration) = ? AND MONTH(uev.date_declaration) >= 9)  -- Déclarations de septembre à décembre
            OR 
            (YEAR(uev.date_declaration) = ? AND MONTH(uev.date_declaration) <= 8)  -- Déclarations de janvier à août
        )
        ORDER BY ue.code, f.nom
    ");
    // Extraire les années de début et de fin
    $annee_debut = substr($annee_universitaire, 0, 4);
    $annee_fin = substr($annee_universitaire, 5, 4);
    $stmt->execute([$id_departement, $semestre, $annee_debut, $annee_fin]);
    $details_vacants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les affectations effectuées
    $stmt = $pdo->prepare("
        SELECT 
            ha.*,
            u.nom as nom_enseignant, u.prenom as prenom_enseignant,
            ue.code as code_ue, ue.intitule as intitule_ue,
            f.nom as nom_filiere
        FROM historique_affectations ha
        JOIN utilisateurs u ON ha.id_utilisateur = u.id
        JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
        JOIN filieres f ON ha.id_filiere = f.id
        WHERE ha.id_departement = ? 
        AND ha.semestre = ?
        AND (
            (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) >= 9)  -- Affectations de septembre à décembre
            OR 
            (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) <= 8)  -- Affectations de janvier à août
        )
        ORDER BY ha.date_affectation DESC
    ");
    // Extraire les années de début et de fin
    $annee_debut = substr($annee_universitaire, 0, 4);
    $annee_fin = substr($annee_universitaire, 5, 4);
    $stmt->execute([$id_departement, $semestre, $annee_debut, $annee_fin]);
    $affectations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les décisions du chef de département
    $stmt = $pdo->prepare("
        SELECT 
            jd.*,
            u.nom as nom_decisionnaire, u.prenom as prenom_decisionnaire,
            CASE 
                WHEN jd.type_entite = 'unite_enseignement' THEN ue.code
                WHEN jd.type_entite = 'utilisateur' THEN CONCAT(ut.nom, ' ', ut.prenom)
                WHEN jd.type_entite = 'affectation' THEN CONCAT(ha.nom_utilisateur, ' - ', ha.code_ue)
                WHEN jd.type_entite = 'voeux_professeurs' THEN CONCAT(up.nom, ' ', up.prenom, ' - ', ue2.code)
                WHEN jd.type_entite = 'unite_vacante' THEN CONCAT(ue3.code, ' - ', f.nom)
                ELSE jd.id_entite
            END as entite_concernee,
            CASE 
                WHEN jd.type_entite = 'unite_enseignement' THEN ue.intitule
                WHEN jd.type_entite = 'utilisateur' THEN ut.role
                WHEN jd.type_entite = 'affectation' THEN CONCAT(ha.type_cours, ' (', ha.volume_horaire, 'h)')
                WHEN jd.type_entite = 'voeux_professeurs' THEN CONCAT(vp.type_ue, ' - Priorité: ', vp.priorite)
                WHEN jd.type_entite = 'unite_vacante' THEN CONCAT(uv.type_cours, ' (', uv.volume_horaire, 'h)')
                ELSE jd.type_entite
            END as details_entite
        FROM journal_decisions jd
        JOIN utilisateurs u ON jd.id_utilisateur_decision = u.id
        LEFT JOIN unites_enseignement ue ON jd.type_entite = 'unite_enseignement' AND jd.id_entite = ue.id
        LEFT JOIN utilisateurs ut ON jd.type_entite = 'utilisateur' AND jd.id_entite = ut.id
        LEFT JOIN historique_affectations ha ON jd.type_entite = 'affectation' AND jd.id_entite = ha.id
        LEFT JOIN voeux_professeurs vp ON jd.type_entite = 'voeux_professeurs' AND jd.id_entite = vp.id
        LEFT JOIN utilisateurs up ON vp.id_utilisateur = up.id
        LEFT JOIN unites_enseignement ue2 ON vp.id_ue = ue2.id
        LEFT JOIN unites_enseignement_vacantes uv ON jd.type_entite = 'unite_vacante' AND jd.id_entite = uv.id
        LEFT JOIN unites_enseignement ue3 ON uv.id_unite_enseignement = ue3.id
        LEFT JOIN filieres f ON uv.id_filiere = f.id
        WHERE jd.id_utilisateur_decision = ? 
        ORDER BY jd.date_decision DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Liste des années universitaires disponibles (avec plus d'options)
$annees_universitaires = [
     '2025-2026',
    '2024-2025',
    '2023-2024', 
    '2022-2023',
    '2021-2022',
    
];

// Récupérer la liste des semestres disponibles
$stmt = $pdo->prepare("SELECT DISTINCT semestre FROM unites_enseignement ORDER BY semestre");
$stmt->execute();
$semestres = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer la liste des rapports déjà générés
$stmt = $pdo->prepare("
    SELECT * FROM rapport_charge_departement
    WHERE id_departement = ?
    ORDER BY date_generation DESC
");
$stmt->execute([$_SESSION['id_departement']]);
$rapports_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si le message doit être supprimé (après 5 secondes)
if (isset($_SESSION['message_timestamp']) && (time() - $_SESSION['message_timestamp'] > 5)) {
    unset($_SESSION['message']);
    unset($_SESSION['message_timestamp']);
}
?>
<head>
 
    <meta charset="UTF-8">
    <title>Génération des Rapports d'Affectation</title>
    <!-- Inclure Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Inclure Chart.js Annotation plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2"></script>
    <!-- Inclure SheetJS pour l'export Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <!-- Bibliothèques pour l'exportation PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--primary-bg);
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background: var(--secondary-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 15px 0;
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            transition: opacity 0.5s ease-out;
        }

        .alert.fade-out {
            opacity: 0;
        }

        .alert-success {
            background-color: rgba(49, 183, 209, 0.2);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }

        .alert-error {
            background-color: rgba(255, 99, 132, 0.2);
            color: #ff6384;
            border: 1px solid #ff6384;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: var(--text-color);
            font-weight: bold;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #2a9fb8;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-info {
             background-color: rgba(112, 134, 171, 0.1);
            color: var(--text-muted);
             border: 1px solid var(--accent-color);
        }
        .btn-info:hover {
             background-color: var(--accent-color);
            color: var(--text-color);
        }
        

        .btn-warning {
            background-color: #ffc107;
            color: var(--primary-bg);
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .rapport-section {
            margin-top: 20px;
        }

        .rapport-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        .rapport-title {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--accent-color);
        }

        .rapport-date {
            font-style: italic;
            color: var(--text-muted);
        }

        .stats-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: var(--primary-bg);
            padding: 15px;
            border-radius: 8px;
            flex: 1;
            min-width: 150px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }

      
            table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: var(--secondary-bg);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px;
            text-align: left;
            color: var(--text-color);
        }

        th {
            background-color: var(--primary-bg);
            color: var(--text-muted);
        }

        

        tr:hover {
            background-color: rgba(49, 183, 209, 0.1);
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 30px 0;
        }

        .chart-wrapper {
            flex: 1;
            min-width: 350px;
            height: 420px;
            min-height: 420px;
            background: var(--primary-bg);
            padding: 30px 20px 20px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .chart-title {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: var(--accent-color);
        }

        .section-title {
            font-size: 1.3em;
            margin-bottom: 20px;
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .decision-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .decision-approved {
            background-color: rgba(49, 183, 209, 0.2);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }

        .decision-rejected {
            background-color: rgba(255, 99, 132, 0.2);
            color: #ff6384;
            border: 1px solid #ff6384;
        }

        .decision-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        select, input[type="file"] {
            background: var(--primary-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
        }

        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        label {
            color: var(--text-muted);
            margin-right: 10px;
        }

        @media print {
            .no-print, .section-buttons, .export-buttons, button {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: none;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .card {
                box-shadow: none;
                border: none;
            }
            .chart-wrapper {
                break-inside: avoid;
            }
        }
    </style>
</head>
<div class="main-container">
    
<div class="container">
        <h2>Génération des Rapports d'Affectation</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div id="message-alert" class="alert <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'alert-error' : 'alert-success' ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
        <?php endif; ?>
        
        <div class="card no-print">
            <h3>Générer un nouveau rapport</h3>
            <form method="post" action="">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="annee_universitaire">Année universitaire:</label>
                        <select name="annee_universitaire" id="annee_universitaire" required>
    <option value="custom">Saisir une année personnalisée</option>
    <?php foreach ($annees_universitaires as $annee): ?>
        <option value="<?= htmlspecialchars($annee) ?>"><?= htmlspecialchars($annee) ?></option>
    <?php endforeach; ?>
</select>
<input type="text" id="annee_custom" name="annee_custom" style="display:none;" placeholder="AAAA-AAAA" pattern="\d{4}-\d{4}" title="Format: AAAA-AAAA">
                    </div>
                    <div>
                        <label for="semestre">Semestre:</label>
                        <select name="semestre" id="semestre" required>
                            <?php foreach ($semestres as $semestre): ?>
                                <option value="<?= htmlspecialchars($semestre) ?>"><?= htmlspecialchars($semestre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="generer_rapport" class="btn btn-primary">Générer le rapport</button>
            </form>
            
            <!-- Formulaire d'import Excel -->
            <h3 style="margin-top: 20px;">Importer des affectations</h3>
            <form method="post" action="" enctype="multipart/form-data">
                <div style="margin-bottom: 15px;">
                    <label for="fichier_excel">Fichier Excel (.xlsx, .xls):</label>
                    <input type="file" name="fichier_excel" id="fichier_excel" accept=".xlsx,.xls" required>
                </div>
                <button type="submit" name="importer_excel" class="btn btn-success">Importer en Excel</button>
                <small style="display: block; margin-top: 10px; color: #666;">
                    Le fichier Excel doit respecter le format des données d'affectation (Date, Enseignant, UE, Filière, Type, Volume Horaire)
                </small>
            </form>
        </div>
        
        <?php if (isset($rapport)): ?>
            <div class="card rapport-section" id="rapport-courant">
                <div class="rapport-header">
                    <div class="rapport-title">
                        Rapport d'affectation - <?= htmlspecialchars($rapport['annee_universitaire']) ?> - Semestre <?= htmlspecialchars($rapport['semestre']) ?>
                    </div>
                    <div class="rapport-date">
                        Généré le <?= date('d/m/Y à H:i', strtotime($rapport['date_generation'])) ?>
                    </div>
                </div>
                
                <div class="stats-summary">
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_cm'] ?></div>
                        <div class="stat-label">Heures CM</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_td'] ?></div>
                        <div class="stat-label">Heures TD</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_tp'] ?></div>
                        <div class="stat-label">Heures TP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures'] ?></div>
                        <div class="stat-label">Total Heures</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['nombre_enseignants'] ?></div>
                        <div class="stat-label">Enseignants</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['nombre_vacataires'] ?></div>
                        <div class="stat-label">UE Vacantes</div>
                    </div>
                </div>
                
                <!-- Section des affectations effectuées -->
                <div class="stats-section">
                    <div class="section-title">
                        <span>Affectations Effectuées</span>
                        <div class="section-buttons">
                            <button class="btn btn-warning" onclick="exportSectionToExcel('affectations-table', 'affectations')">Exporter en Excel</button>
                        </div>
                    </div>
                    <table id="affectations-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Enseignant</th>
                                <th>UE</th>
                                <th>Filière</th>
                                <th>Type</th>
                                <th>Volume Horaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($affectations as $affectation): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($affectation['date_affectation'])) ?></td>
                                    <td><?= htmlspecialchars($affectation['nom_enseignant'] . ' ' . htmlspecialchars($affectation['prenom_enseignant'])) ?></td>
                                    <td><?= htmlspecialchars($affectation['code_ue'] . ' - ' . htmlspecialchars($affectation['intitule_ue'])) ?></td>
                                    <td><?= htmlspecialchars($affectation['nom_filiere']) ?></td>
                                    <td><?= htmlspecialchars($affectation['type_cours']) ?></td>
                                    <td><?= $affectation['volume_horaire'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Section des décisions prises -->
                <div class="stats-section">
                    <div class="section-title">
                        <span>Décisions Prises</span>
                        <div class="section-buttons">
                            <button class="btn btn-warning" onclick="exportSectionToExcel('decisions-table', 'decisions')">Exporter en Excel</button>
                        </div>
                    </div>
                    <table id="decisions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type Entité</th>
                                <th>Entité concernée</th>
                               
                                <th>Décisionnaire</th>
                                <th>Ancien Statut</th>
                                <th>Nouveau Statut</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($decisions as $decision): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($decision['date_decision'])) ?></td>
                                    <td><?= htmlspecialchars($decision['type_entite']) ?></td>
                                    <td><?= htmlspecialchars($decision['entite_concernee']) ?></td>

                                    <td><?= htmlspecialchars($decision['nom_decisionnaire'] . ' ' . htmlspecialchars($decision['prenom_decisionnaire'])) ?></td>
                                    <td>
                                        <span class="decision-badge <?= 
                                            $decision['ancien_statut'] === 'approuve' ? 'decision-approved' : 
                                            ($decision['ancien_statut'] === 'rejete' ? 'decision-rejected' : 'decision-pending') 
                                        ?>">
                                            <?= htmlspecialchars($decision['ancien_statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="decision-badge <?= 
                                            $decision['nouveau_statut'] === 'approuve' ? 'decision-approved' : 
                                            ($decision['nouveau_statut'] === 'rejete' ? 'decision-rejected' : 'decision-pending') 
                                        ?>">
                                            <?= htmlspecialchars($decision['nouveau_statut']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($decision['commentaire']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Section des charges par enseignant -->
                <div class="stats-section">
                    <div class="section-title">
                        <span>Détail des charges par enseignant</span>
                        <div class="section-buttons">
                            <button class="btn btn-warning" onclick="exportSectionToExcel('enseignants-table', 'charges_enseignants')">Exporter en Excel</button>
                        </div>
                    </div>
                    <table id="enseignants-table">
                        <thead>
                            <tr>
                                <th>Enseignant</th>
                                <th>Rôle</th>
                                <th>Heures CM</th>
                                <th>Heures TD</th>
                                <th>Heures TP</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details_enseignants as $enseignant): ?>
                                <tr>
                                    <td><?= htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']) ?></td>
                                    <td><?= htmlspecialchars($enseignant['role']) ?></td>
                                    <td><?= $enseignant['heures_cm'] ?></td>
                                    <td><?= $enseignant['heures_td'] ?></td>
                                    <td><?= $enseignant['heures_tp'] ?></td>
                                    <td><strong><?= $enseignant['total_heures'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Section des UE vacantes -->
                <div class="stats-section">
                    <div class="section-title">
                        <span>Unités d'enseignement déclarées vacantes</span>
                        <div class="section-buttons">
                            <button class="btn btn-warning" onclick="exportSectionToExcel('ue-vacantes-table', 'ue_vacantes')">Exporter en Excel</button>
                        </div>
                    </div>
                    <?php if (empty($details_vacants)): ?>
                        <p>Aucune unité d'enseignement n'a été déclarée vacante pour cette période.</p>
                    <?php else: ?>
                        <table id="ue-vacantes-table">
                            <thead>
                                <tr>
                                    <th>Code UE</th>
                                    <th>Intitulé</th>
                                    <th>Filière</th>
                                    <th>Type</th>
                                    <th>Volume horaire</th>
                                    <th>Date de déclaration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details_vacants as $vacant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($vacant['code']) ?></td>
                                        <td><?= htmlspecialchars($vacant['intitule']) ?></td>
                                        <td><?= htmlspecialchars($vacant['nom_filiere']) ?></td>
                                        <td><?= htmlspecialchars($vacant['type_cours']) ?></td>
                                        <td><?= $vacant['volume_horaire'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($vacant['date_declaration'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Section pour les statistiques avancées avec les graphiques -->
                <div class="stats-section">
                    <h3 class="section-title">Statistiques Avancées</h3>
                    
                    <div class="chart-container">
                        <!-- Graphique 1: Evolution historique -->
                        <div class="chart-wrapper">
                            <div class="chart-title">Évolution des charges (5 derniers semestres)</div>
                            <canvas id="historiqueChart"></canvas>
                        </div>
                        
                        <!-- Graphique 2: Répartition par filière -->
                        <div class="chart-wrapper">
                            <div class="chart-title">Répartition des UE vacantes par filière</div>
                            <canvas id="filiereChart"></canvas>
                        </div>
                        
                        <!-- Graphique 3: Répartition des heures par type de cours -->
                        <div class="chart-wrapper">
                            <div class="chart-title">Répartition des heures par type de cours</div>
                            <canvas id="typeCoursChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="export-buttons no-print">
                    <button class="btn btn-info" onclick="exportToPDF()">Exporter tout en PDF</button>
                    <button class="btn btn-primary print-button" onclick="window.print()">Imprimer le rapport</button>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card no-print">
            <h3>Rapports précédemment générés</h3>
            <?php if (empty($rapports_existants)): ?>
                <p>Aucun rapport n'a encore été généré.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Année universitaire</th>
                            <th>Semestre</th>
                            <th>Total heures</th>
                            <th>Enseignants</th>
                            <th>UE Vacantes</th>
                            <th>Date de génération</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rapports_existants as $rapport_existant): ?>
                            <tr>
                                <td><?= htmlspecialchars($rapport_existant['annee_universitaire']) ?></td>
                                <td><?= htmlspecialchars($rapport_existant['semestre']) ?></td>
                                <td><?= $rapport_existant['total_heures'] ?></td>
                                <td><?= $rapport_existant['nombre_enseignants'] ?></td>
                                <td><?= $rapport_existant['nombre_vacataires'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($rapport_existant['date_generation'])) ?></td>
                                <td>
                                    <a href="rapport_details.php?id=<?= $rapport_existant['id'] ?>" class="btn btn-info">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <script>
    // Fonction pour faire disparaître le message après 5 secondes
    document.addEventListener('DOMContentLoaded', function() {
        const messageAlert = document.getElementById('message-alert');
        if (messageAlert) {
            setTimeout(function() {
                messageAlert.classList.add('fade-out');
                setTimeout(function() {
                    messageAlert.remove();
                }, 500);
            }, 5000);
        }
        // Gestion du champ d'année personnalisée
  // Gestion du champ d'année personnalisée
const anneeSelect = document.getElementById('annee_universitaire');
const anneeCustom = document.getElementById('annee_custom');

if (anneeSelect && anneeCustom) {
    // Vérifier au chargement initial si le champ personnalisé doit être affiché
    if (anneeSelect.value === 'custom') {
        anneeCustom.style.display = 'inline-block';
        anneeCustom.required = true;
    }
    
    anneeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            anneeCustom.style.display = 'inline-block';
            anneeCustom.required = true;
        } else {
            anneeCustom.style.display = 'none';
            anneeCustom.required = false;
            anneeCustom.value = '';
        }
    });
    
    // Validation du formulaire
    const rapportForm = document.querySelector('form[action=""]');
    if (rapportForm) {
        rapportForm.addEventListener('submit', function(e) {
            if (anneeSelect.value === 'custom') {
                const customValue = anneeCustom.value.trim();
                const regex = /^\d{4}-\d{4}$/;
                
                if (!regex.test(customValue)) {
                    e.preventDefault();
                    alert('Format d\'année incorrect. Utilisez le format AAAA-AAAA (exemple: 2025-2026)');
                }
            }
        });
    }
}
        // Initialiser les graphiques si le rapport est affiché
        <?php if (isset($rapport)): ?>
            // 1. Graphique des types de cours (Doughnut)
            const typeCoursCtx = document.getElementById('typeCoursChart').getContext('2d');
            const typeCoursChart = new Chart(typeCoursCtx, {
                type: 'doughnut',
                data: {
                    labels: ['CM', 'TD', 'TP'],
                    datasets: [{
                        data: [
                            <?= $rapport['total_heures_cm'] ?>, 
                            <?= $rapport['total_heures_td'] ?>, 
                            <?= $rapport['total_heures_tp'] ?>
                        ],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value}h (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });

            // 2. Graphique historique (Line)
            const historiqueCtx = document.getElementById('historiqueChart').getContext('2d');
            const historiqueChart = new Chart(historiqueCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php foreach ($historique_charges as $charge): ?>
                            '<?= $charge['annee_universitaire'] ?> S<?= $charge['semestre'] ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Total heures',
                        data: [
                            <?php foreach ($historique_charges as $charge): ?>
                                <?= $charge['total_heures'] ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + 'h';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Heures'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    elements: {
                        line: {
                            cubicInterpolationMode: 'monotone'
                        }
                    }
                }
            });

            // 3. Graphique répartition par filière (Polar Area)
            const filiereCtx = document.getElementById('filiereChart').getContext('2d');
            const filiereChart = new Chart(filiereCtx, {
                type: 'polarArea',
                data: {
                    labels: [
                        <?php foreach ($repartition_filieres as $filiere): ?>
                            '<?= $filiere['filiere'] ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'UE Vacantes',
                        data: [
                            <?php foreach ($repartition_filieres as $filiere): ?>
                                <?= $filiere['nb_ue_vacantes'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw + ' UE vacantes';
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
        <?php endif; ?>
    });

    // Fonction pour exporter une section en Excel
    function exportSectionToExcel(tableId, fileName) {
        const table = document.getElementById(tableId);
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
        
        // Générer un nom de fichier avec la date
        const today = new Date();
        const dateStr = today.toISOString().split('T')[0];
        const finalFileName = `rapport_${fileName}_${dateStr}.xlsx`;
        
        XLSX.writeFile(wb, finalFileName);
    }

   
function exportToPDF() {
    // Vérifier si les bibliothèques sont déjà chargées
    if (typeof html2canvas === 'undefined' || typeof jspdf === 'undefined') {
        // Ajouter les scripts nécessaires
        const html2canvasScript = document.createElement('script');
        html2canvasScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
        document.head.appendChild(html2canvasScript);
        
        const jsPdfScript = document.createElement('script');
        jsPdfScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        document.head.appendChild(jsPdfScript);
        
        alert('Chargement des bibliothèques PDF... Veuillez réessayer dans quelques secondes.');
        return;
    }
    
    const element = document.getElementById('rapport-courant');
    if (!element) {
        alert('Rapport non trouvé');
        return;
    }
    
    // Montrer un message de chargement
    const loadingMsg = document.createElement('div');
    loadingMsg.innerHTML = 'Génération du PDF en cours...';
    loadingMsg.style.position = 'fixed';
    loadingMsg.style.top = '50%';
    loadingMsg.style.left = '50%';
    loadingMsg.style.transform = 'translate(-50%, -50%)';
    loadingMsg.style.padding = '20px';
    loadingMsg.style.background = 'rgba(0,0,0,0.7)';
    loadingMsg.style.color = 'white';
    loadingMsg.style.borderRadius = '5px';
    loadingMsg.style.zIndex = '9999';
    document.body.appendChild(loadingMsg);
    
    // Cacher temporairement tous les boutons et éléments non imprimables
    const noPrintElements = document.querySelectorAll('.no-print');
    const sectionButtons = document.querySelectorAll('.section-buttons');
    const exportButtons = document.querySelectorAll('.export-buttons');
    
    // Sauvegarder l'état original pour pouvoir le restaurer
    const originalDisplays = {
        noPrint: [],
        sectionButtons: [],
        exportButtons: []
    };
    
    noPrintElements.forEach((el, index) => {
        originalDisplays.noPrint[index] = el.style.display;
        el.style.display = 'none';
    });
    
    sectionButtons.forEach((el, index) => {
        originalDisplays.sectionButtons[index] = el.style.display;
        el.style.display = 'none';
    });
    
    exportButtons.forEach((el, index) => {
        originalDisplays.exportButtons[index] = el.style.display;
        el.style.display = 'none';
    });
    
    html2canvas(element, {
        scale: 1.5, // Meilleure qualité
        useCORS: true,
        logging: false
    }).then(function(canvas) {
        // Restaurer les éléments cachés
        noPrintElements.forEach((el, index) => {
            el.style.display = originalDisplays.noPrint[index];
        });
        
        sectionButtons.forEach((el, index) => {
            el.style.display = originalDisplays.sectionButtons[index];
        });
        
        exportButtons.forEach((el, index) => {
            el.style.display = originalDisplays.exportButtons[index];
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.8);
        const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
        
        const imgWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;
        
        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
        
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }
        
        // Retirer le message de chargement
        document.body.removeChild(loadingMsg);
        
        // Générer le PDF
        const today = new Date();
        const dateStr = today.toISOString().split('T')[0];
        pdf.save(`rapport_pdf_${dateStr}.pdf`);
    });
}
    </script>