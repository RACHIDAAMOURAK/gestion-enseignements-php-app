<?php
session_start();
require_once 'config/database.php';

// Affichage des erreurs PHP pour debug (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vacataire') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données POST
        if (!isset($_POST['module_id']) || !isset($_POST['session_type'])) {
            throw new Exception("Données manquantes dans la requête.");
        }
        
        $module_id = filter_var($_POST['module_id'], FILTER_VALIDATE_INT);
        $session_type = trim($_POST['session_type']);
        $vacataire_id = $_SESSION['user_id'];
        
        // Validation des données
        if ($module_id === false || $module_id <= 0) {
            throw new Exception("ID du module invalide.");
        }
        
        if (empty($session_type)) {
            throw new Exception("Type de session requis.");
        }
        
        // Validation du type de session selon l'enum de la DB
        $valid_session_types = ['normale', 'rattrapage'];
        if (!in_array($session_type, $valid_session_types)) {
            throw new Exception("Type de session invalide. Utilisez 'normale' ou 'rattrapage'.");
        }
        
        // Vérifier que l'unité d'enseignement existe
        $stmt = $pdo->prepare("SELECT id FROM unites_enseignement WHERE id = ?");
        $stmt->execute([$module_id]);
        if (!$stmt->fetch()) {
            throw new Exception("L'unité d'enseignement avec l'ID $module_id n'existe pas.");
        }
        
        // Vérification que le module appartient bien au vacataire
        $stmt = $pdo->prepare("
            SELECT id FROM historique_affectations_vacataire 
            WHERE id_unite_enseignement = ? AND id_vacataire = ?
        ");
        $stmt->execute([$module_id, $vacataire_id]);
        $affectation = $stmt->fetch();
        
        if (!$affectation) {
            throw new Exception("Vous n'êtes pas affecté à ce module (ID: $module_id).");
        }
        
        // Vérification du fichier uploadé
        if (!isset($_FILES['notes_file']) || $_FILES['notes_file']['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée.',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille spécifiée dans le formulaire.',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque.',
                UPLOAD_ERR_EXTENSION => 'Upload arrêté par une extension PHP.'
            ];
            
            $error_code = $_FILES['notes_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new Exception($error_messages[$error_code] ?? 'Erreur inconnue lors de l\'upload.');
        }
        
        $file = $_FILES['notes_file'];
        
        // Validation du type de fichier
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception("Format de fichier non autorisé. Utilisez Excel (.xlsx, .xls) ou CSV.");
        }
        
        // Validation de la taille du fichier (exemple: max 10MB)
        $max_file_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_file_size) {
            throw new Exception("Le fichier est trop volumineux (max 10MB).");
        }
        
        // Validation du nom de fichier
        if (empty($file['name']) || strlen($file['name']) > 255) {
            throw new Exception("Nom de fichier invalide.");
        }
        
        // Création du dossier d'upload si nécessaire
        $upload_dir = '../uploads/notes/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier d'upload.");
            }
        }
        
        // Génération d'un nom de fichier sécurisé
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $file_name = uniqid() . '_' . $safe_filename;
        $file_path = $upload_dir . $file_name;
        
        // Vérification que le fichier n'existe pas déjà
        if (file_exists($file_path)) {
            throw new Exception("Un fichier avec ce nom existe déjà.");
        }
        
        // Upload du fichier
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception("Erreur lors de l'upload du fichier.");
        }
        
        // Lire le fichier Excel et extraire les notes
        require_once 'vendor/autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        // Vérification du format des données
        if (count($data) < 2) {
            throw new Exception("Le fichier ne contient pas assez de données");
        }

        // Vérification de la structure du fichier (plus flexible)
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        $numero_col = array_search('numero_etudiant', $headers);
        $note_col = array_search('note', $headers);
        
        if ($numero_col === false || $note_col === false) {
            throw new Exception("Le format du fichier est incorrect. Les colonnes 'numero_etudiant' et 'note' sont requises.");
        }

        $pdo->beginTransaction();

        try {
            // Enregistrement du fichier dans la table fichiers_notes
            $stmt_fichier = $pdo->prepare("INSERT INTO fichiers_notes (
                id_unite_enseignement,
                id_enseignant,
                type_session,
                nom_fichier,
                chemin_fichier,
                statut
            ) VALUES (?, ?, ?, ?, ?, 'traite')");
            $stmt_fichier->execute([
                $module_id,
                $vacataire_id,
                $session_type,
                $file['name'],
                $file_name
            ]);
            $id_fichier = $pdo->lastInsertId();

            // Supprimer les anciennes notes pour cette UE et cette session
            $stmt_delete = $pdo->prepare("DELETE FROM notes WHERE id_unite_enseignement = ? AND type_session = ?");
            $stmt_delete->execute([$module_id, $session_type]);

            // Récupérer tous les étudiants en une seule requête pour optimiser
            $stmt_all_students = $pdo->prepare("SELECT id, numero_etudiant FROM etudiants");
            $stmt_all_students->execute();
            $students_map = [];
            while ($student = $stmt_all_students->fetch()) {
                $students_map[$student['numero_etudiant']] = $student['id'];
            }

            // Préparation de la requête d'insertion des notes avec gestion des conflits
            $stmt = $pdo->prepare("INSERT INTO notes (
                id_unite_enseignement,
                id_etudiant,
                type_session,
                note,
                date_soumission,
                id_enseignant,
                statut,
                fichier_path
            ) VALUES (?, ?, ?, ?, NOW(), ?, 'soumise', ?)
            ON DUPLICATE KEY UPDATE
                note = VALUES(note),
                date_soumission = NOW(),
                id_enseignant = VALUES(id_enseignant),
                fichier_path = VALUES(fichier_path)");

            $notes_inserees = 0;
            $notes_mises_a_jour = 0;
            $erreurs = [];

            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                
                // Vérifier que la ligne contient des données
                if (empty($row[$numero_col]) && empty($row[$note_col])) {
                    continue; // Ignorer les lignes vides
                }
                
                if (isset($row[$numero_col]) && isset($row[$note_col])) {
                    $numero_etudiant = trim($row[$numero_col]);
                    $note_value = str_replace(',', '.', trim($row[$note_col]));
                    
                    // Gérer les cas où la note est vide ou "ABS"
                    if (empty($note_value) || strtoupper($note_value) === 'ABS') {
                        $note = 0; // Note par défaut pour absent
                    } else {
                        $note = floatval($note_value);
                    }

                    // Vérifier que le numéro est au format ETxxx
                    if (!preg_match('/^ET\d{3}$/', $numero_etudiant)) {
                        $erreurs[] = "Ligne " . ($i + 1) . " : Le numéro étudiant doit être au format ETxxx (ex: ET001)";
                        continue;
                    }

                    // Vérifier si l'étudiant existe
                    if (!isset($students_map[$numero_etudiant])) {
                        $erreurs[] = "Ligne " . ($i + 1) . " : Étudiant non trouvé avec le numéro " . $numero_etudiant;
                        continue;
                    }

                    // Validation de la note
                    if ($note < 0 || $note > 20) {
                        $erreurs[] = "Ligne " . ($i + 1) . " : La note doit être comprise entre 0 et 20 (note: $note)";
                        continue;
                    }

                    try {
                        $stmt->execute([
                            $module_id,
                            $students_map[$numero_etudiant],
                            $session_type,
                            $note,
                            $vacataire_id,
                            $file_name
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            $notes_inserees++;
                        }
                        
                    } catch (PDOException $e) {
                        $erreurs[] = "Ligne " . ($i + 1) . " : Erreur pour l'étudiant " . $numero_etudiant . " - " . $e->getMessage();
                    }
                }
            }

            // Mise à jour du statut du fichier
            if ($notes_inserees > 0) {
                $stmt_update_file = $pdo->prepare("UPDATE fichiers_notes SET statut = 'traite', date_traitement = NOW() WHERE id = ?");
                $stmt_update_file->execute([$id_fichier]);
            }

            $pdo->commit();

            // Message de succès détaillé
            $message = "Upload terminé avec succès !";
            if ($notes_inserees > 0) {
                $message .= "\n✓ $notes_inserees notes ont été enregistrées.";
            }
            if (!empty($erreurs)) {
                $message .= "\n⚠️ Erreurs rencontrées :\n" . implode("\n", array_slice($erreurs, 0, 10));
                if (count($erreurs) > 10) {
                    $message .= "\n... et " . (count($erreurs) - 10) . " autres erreurs.";
                }
            }
            
            $_SESSION['success_message'] = $message;
            header('Location: gestion_notes.php');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            throw new Exception("Erreur lors de l'enregistrement des notes : " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Erreur upload notes - Utilisateur ID: " . ($_SESSION['user_id'] ?? 'unknown') . " - " . $e->getMessage());
    }
    
    header('Location: dashboard.php');
    exit();
}
?>