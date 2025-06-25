<?php
require_once 'includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de login du groupe 1
    header('Location:/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier si l'utilisateur est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: index.php?error=' . urlencode('Seuls les enseignants peuvent uploader des notes.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier si tous les champs requis sont présents
        if (!isset($_POST['id_ue']) || !isset($_POST['type_session']) || !isset($_FILES['fichier'])) {
            throw new Exception('Tous les champs sont obligatoires');
        }

        $id_ue = $_POST['id_ue'];
        $type_session = $_POST['type_session'];
        $fichier = $_FILES['fichier'];

        // Vérification de l'extension du fichier
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception("Format de fichier non autorisé. Formats acceptés : XLSX, XLS, CSV");
        }

        // Vérification du type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $fichier['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain' // Pour les fichiers CSV
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception("Le fichier n'est pas un fichier Excel ou CSV valide");
        }

        // Vérifier si l'enseignant est responsable de cette UE
        $stmt = $conn->prepare('
            SELECT id_utilisateur 
            FROM historique_affectations 
            WHERE id_unite_enseignement = ? 
            AND type_cours = "CM"
            ORDER BY date_affectation DESC 
            LIMIT 1
        ');
        $stmt->execute([$id_ue]);
        $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$enseignant) {
            throw new Exception('Aucun enseignant responsable trouvé pour cette UE');
        }

        $id_enseignant = $enseignant['id_utilisateur'];

        // Vérification des erreurs d'upload
        if ($fichier['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale autorisée par PHP",
                UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale autorisée par le formulaire",
                UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement uploadé",
                UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été uploadé",
                UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant",
                UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque",
                UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté l'upload du fichier"
            ];
            throw new Exception($error_messages[$fichier['error']] ?? "Erreur lors de l'upload du fichier");
        }

        // Création du dossier d'upload si nécessaire
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Impossible de créer le dossier d'upload");
            }
        }

        // Génération d'un nom de fichier unique
        $new_filename = uniqid() . '_notes_' . $id_ue . '_' . $type_session . '.' . $extension;
        $upload_path = $upload_dir . $new_filename;

        // Déplacement du fichier
        if (!move_uploaded_file($fichier['tmp_name'], $upload_path)) {
            throw new Exception("Erreur lors du déplacement du fichier");
        }

        try {
            // Lecture du fichier Excel/CSV
            $spreadsheet = IOFactory::load($upload_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            // Vérification du format des données
            if (count($data) < 2) {
                throw new Exception("Le fichier ne contient pas assez de données");
            }

            // Vérification de la structure du fichier
            if (!isset($data[0][0]) || !isset($data[0][1]) || 
                !in_array(strtolower($data[0][0]), ['numero_etudiant']) || 
                !in_array(strtolower($data[0][1]), ['note'])) {
                throw new Exception("Le format du fichier est incorrect. La première ligne doit contenir 'numero_etudiant' et 'Note'");
            }

        } catch (Exception $e) {
            // Si erreur lors de la lecture, on supprime le fichier et on remonte l'erreur
            unlink($upload_path);
            throw new Exception("Erreur de lecture du fichier : " . $e->getMessage());
        }

        // Début de la transaction
        $conn->beginTransaction();

        // Enregistrement du fichier dans la table fichiers_notes
        $stmt_fichier = $conn->prepare("INSERT INTO fichiers_notes (
            id_unite_enseignement,
            id_enseignant,
            type_session,
            nom_fichier,
            chemin_fichier,
            statut
        ) VALUES (?, ?, ?, ?, ?, 'non_traite')");

        $stmt_fichier->execute([
            $id_ue,
            $id_enseignant,
            $type_session,
            $fichier['name'],
            $new_filename
        ]);

        $id_fichier = $conn->lastInsertId();

        // Supprimer les anciennes notes pour cette UE et cette session
        $stmt_delete = $conn->prepare("DELETE FROM notes WHERE id_unite_enseignement = ? AND type_session = ?");
        $stmt_delete->execute([$id_ue, $type_session]);

        // Préparation de la requête pour récupérer l'ID de l'étudiant
        $stmt_etudiant = $conn->prepare("SELECT id FROM etudiants WHERE numero_etudiant = ?");

        // Préparation de la requête d'insertion des notes
        $stmt = $conn->prepare("INSERT INTO notes (
            id_unite_enseignement,
            id_etudiant,
            type_session,
            note,
            date_soumission,
            id_enseignant,
            statut,
            fichier_path
        ) VALUES (?, ?, ?, ?, NOW(), ?, 'soumise', ?)");

        // Traitement des notes
        $notes_inserees = 0;
        $erreurs = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            if (isset($row[0]) && isset($row[1])) {
                $numero_etudiant = trim($row[0]);
                
                // Vérifier que le numéro est au format ETxxx
                if (!preg_match('/^ET\d{3}$/', $numero_etudiant)) {
                    $erreurs[] = "Ligne " . ($i + 1) . " : Le numéro étudiant doit être au format ETxxx (ex: ET001)";
                    continue;
                }
                
                $note = str_replace(',', '.', trim($row[1])); // Remplace la virgule par un point
                $note = floatval($note);

                // Récupérer l'ID de l'étudiant avec le numéro complet
                $stmt_etudiant = $conn->prepare("SELECT id FROM etudiants WHERE numero_etudiant = ?");
                $stmt_etudiant->execute([$numero_etudiant]);
                $etudiant = $stmt_etudiant->fetch(PDO::FETCH_ASSOC);

                if (!$etudiant) {
                    $erreurs[] = "Ligne " . ($i + 1) . " : Étudiant non trouvé avec le numéro " . $numero_etudiant;
                    continue;
                }

                if ($note >= 0 && $note <= 20) {
                    try {
                        $stmt->execute([
                            $id_ue,
                            $etudiant['id'],
                            $type_session,
                            $note,
                            $id_enseignant,
                            $new_filename
                        ]);
                        $notes_inserees++;
                    } catch (PDOException $e) {
                        $erreurs[] = "Ligne " . ($i + 1) . " : Erreur pour l'étudiant " . $numero_etudiant;
                    }
                } else {
                    $erreurs[] = "Ligne " . ($i + 1) . " : La note doit être comprise entre 0 et 20";
                }
            }
        }

        // Mise à jour du statut du fichier
        if ($notes_inserees > 0) {
            $stmt = $conn->prepare("UPDATE fichiers_notes SET statut = 'traite' WHERE id = ?");
            $stmt->execute([$id_fichier]);
        }

        // Validation de la transaction
        $conn->commit();

        // Message de succès avec avertissements éventuels
        $message = "Les notes ont été uploadées avec succès. $notes_inserees notes ont été traitées.";
        if (!empty($erreurs)) {
            $message .= "\nAttention : " . implode("\n", $erreurs);
        }

        header('Location: index.php?success=1&notes=' . $notes_inserees . '&message=' . urlencode($message));
        exit;

    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        // Suppression du fichier uploadé en cas d'erreur
        if (isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }

        // Redirection avec message d'erreur
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}
?> 