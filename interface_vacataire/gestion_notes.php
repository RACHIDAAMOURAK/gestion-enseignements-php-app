<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et vacataire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vacataire') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Récupérer les messages de la session (après redirection)
if (isset($_SESSION['upload_message'])) {
    $message = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}
if (isset($_SESSION['upload_error'])) {
    $error = $_SESSION['upload_error'];
    unset($_SESSION['upload_error']);
}

// Récupérer les infos du vacataire pour l'affichage du header
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.nom_utilisateur,
        u.email,
        u.prenom,
        u.nom,
        u.role,
        u.specialite,
        u.id_departement,
        d.nom as departement_nom,
        sp.nom as specialite_nom
    FROM utilisateurs u
    LEFT JOIN departements d ON u.id_departement = d.id
    LEFT JOIN utilisateur_specialites us ON u.id = us.id_utilisateur
    LEFT JOIN specialites sp ON us.id_specialite = sp.id
    WHERE u.id = ? AND u.role = 'vacataire' AND u.actif = 1
");
$stmt->execute([$user_id]);
$vacataire = $stmt->fetch();

// Récupérer les modules affectés au vacataire
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT ue.id, ue.intitule, ue.code 
        FROM unites_enseignement ue
        JOIN historique_affectations_vacataire hav ON ue.id = hav.id_unite_enseignement
        WHERE hav.id_vacataire = ?
        ORDER BY ue.intitule
    ");
    $stmt->execute([$user_id]);
    $modules_affectes = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erreur lors de la récupération des modules affectés.";
    $modules_affectes = [];
}

// Traitement du formulaire d'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = $_POST['module_id'] ?? null;
    $session_type = $_POST['session_type'] ?? null;
    
    // Vérifications
    if (empty($module_id)) {
        $_SESSION['upload_error'] = "Veuillez sélectionner un module.";
    } elseif (empty($session_type)) {
        $_SESSION['upload_error'] = "Veuillez sélectionner un type de session.";
    } elseif (!isset($_FILES['notes_file']) || $_FILES['notes_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = "Veuillez sélectionner un fichier valide.";
    } else {
        // Vérifier que le module est bien affecté au vacataire
        $autorise = false;
        foreach ($modules_affectes as $mod) {
            if ($mod['id'] == $module_id) {
                $autorise = true;
                break;
            }
        }
        if (!$autorise) {
            $_SESSION['upload_error'] = "Vous n'êtes pas autorisé à uploader des notes pour ce module.";
        } else {
            // Traitement du fichier
            $fichier = $_FILES['notes_file'];
            $nom_original = $fichier['name'];
            $extension = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                $_SESSION['upload_error'] = "Seuls les fichiers Excel (.xlsx, .xls) ou CSV sont acceptés.";
            } else {
                $nom_fichier = time() . '_' . $user_id . '_' . $module_id . '.' . $extension;
                $chemin_upload = '../uploads/notes/' . $nom_fichier;
                $dossier_upload = dirname($chemin_upload);
                if (!is_dir($dossier_upload)) {
                    if (!mkdir($dossier_upload, 0755, true)) {
                        $_SESSION['upload_error'] = "Impossible de créer le dossier d'upload.";
                    }
                }
                if (!isset($_SESSION['upload_error'])) {
                    if (move_uploaded_file($fichier['tmp_name'], $chemin_upload)) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO fichiers_notes (
                                    id_unite_enseignement, 
                                    id_enseignant, 
                                    type_session, 
                                    nom_fichier, 
                                    chemin_fichier, 
                                    date_upload, 
                                    statut
                                ) VALUES (?, ?, ?, ?, ?, NOW(), 'traite')
                            ");
                            $result = $stmt->execute([
                                $module_id,
                                $user_id,
                                $session_type,
                                $nom_original,
                                $nom_fichier
                            ]);
                            if ($result) {
                                // Lire le fichier Excel et extraire les notes
                                require_once 'vendor/autoload.php';
                                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($chemin_upload);
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
                                                    $user_id,
                                                    $nom_fichier
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
                                        $stmt_update_file = $pdo->prepare("UPDATE fichiers_notes SET statut = 'traite', date_upload = NOW() WHERE id = ?");
                                        $stmt_update_file->execute([$pdo->lastInsertId()]);
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
                                    
                                    $_SESSION['upload_message'] = $message;
                                } catch (Exception $e) {
                                    if ($pdo->inTransaction()) {
                                        $pdo->rollBack();
                                    }
                                    if (file_exists($chemin_upload)) {
                                        unlink($chemin_upload);
                                    }
                                    throw new Exception("Erreur lors de l'enregistrement des notes : " . $e->getMessage());
                                }
                            } else {
                                $_SESSION['upload_error'] = "Erreur lors de l'enregistrement en base de données.";
                                if (file_exists($chemin_upload)) {
                                    unlink($chemin_upload);
                                }
                            }
                        } catch (Exception $e) {
                            $_SESSION['upload_error'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
                            if (file_exists($chemin_upload)) {
                                unlink($chemin_upload);
                            }
                        }
                    } else {
                        $_SESSION['upload_error'] = "Erreur lors du téléchargement du fichier.";
                    }
                }
            }
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Historique des fichiers uploadés
$historique_fichiers = [];
try {
    $stmt = $pdo->prepare("
        SELECT fn.id, fn.nom_fichier, fn.type_session, fn.statut, fn.date_upload,
               ue.intitule as nom_module, ue.code
        FROM fichiers_notes fn
        JOIN unites_enseignement ue ON fn.id_unite_enseignement = ue.id
        WHERE fn.id_enseignant = ?
        ORDER BY fn.date_upload DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $historique_fichiers = $stmt->fetchAll();
} catch (Exception $e) {
    // Erreur silencieuse pour l'historique
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes - Vacataire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
            --header-bg: var(--secondary-bg);
            --top-nav-height: 60px;
            --footer-height: 50px;
            --sidebar-width-collapsed: 50px;
            --sidebar-width-expanded: 250px;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --content-padding: var(--spacing-md);
            --section-margin: var(--spacing-md);
            --card-padding: var(--spacing-md);
            --form-spacing: var(--spacing-md);
            --transition-speed: 0.3s;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--primary-bg); color: var(--text-color); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-width-collapsed); background-color: var(--primary-bg); border-right: 1px solid var(--border-color); transition: width 0.3s ease; z-index: 1000; overflow: hidden; }
        body.menu-expanded .sidebar { width: var(--sidebar-width-expanded); }
        .sidebar-logo { padding: 1.5rem 0; color: var(--accent-color); text-align: center; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; }
        .menu-items { display: flex; flex-direction: column; height: calc(100% - 60px); padding: 1rem 0; justify-content: space-between; }
        .menu-items-top { display: flex; flex-direction: column; gap: 1rem; }
        .menu-items-bottom { margin-top: auto; display: flex; flex-direction: column; gap: 1rem; }
        .menu-item { color: var(--text-muted); text-decoration: none; padding: 0.75rem 1rem; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem; white-space: nowrap; cursor: pointer; }
        .menu-item i { font-size: 1.1rem; width: 20px; text-align: center; transition: color 0.2s ease; display: flex; align-items: center; justify-content: center; color: #8A9CC9; }
        .menu-toggle { color: var(--accent-color); text-align: center; padding: 1.2rem 0; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; margin-top: 0.5rem; width: 100%; }
        .menu-toggle i { font-size: 1.5rem; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .menu-toggle:hover i { transform: rotate(180deg); }
        body.menu-expanded .menu-toggle i { transform: rotate(-180deg); }
        body.menu-expanded .menu-toggle:hover i { transform: rotate(-360deg); }
        .menu-toggle .menu-text { display: none; }
        body.menu-expanded .menu-toggle { justify-content: flex-start; padding-left: 1.5rem; }
        .menu-item:hover, .menu-item.active { color: var(--text-color); background-color: rgba(49, 183, 209, 0.1); }
        .menu-item.active { background-color: var(--secondary-bg); border-left: 3px solid var(--accent-color); }
        .menu-item:hover i, .menu-item.active i { color: var(--accent-color) !important; }
        .menu-text { opacity: 0; visibility: hidden; transition: opacity 0.2s, visibility 0.2s; font-size: 0.95rem; }
        body.menu-expanded .menu-text { opacity: 1; visibility: visible; }
        .top-nav { position: fixed; top: 0; right: 0; left: var(--sidebar-width-collapsed); z-index: 999; height: var(--top-nav-height); background-color: var(--primary-bg); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; padding: 0 var(--spacing-md); transition: left 0.3s ease; }
        body.menu-expanded .top-nav { left: var(--sidebar-width-expanded); }
        .container-fluid { width: 100%; }
        .user-menu { display: flex; align-items: center; gap: 1.5rem; justify-content: flex-end; width: 100%; }
        .user-profile { display: flex; align-items: center; gap: 0.75rem; color: var(--text-color); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 0.375rem; transition: all 0.2s; }
        .user-profile:hover { background-color: rgba(49, 183, 209, 0.1); }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background-color: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 500; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { color: var(--text-color); font-size: 0.875rem; font-weight: 500; }
        .user-role { color: #8A9CC9; font-size: 0.75rem; }
        .action-button { color: #8A9CC9; font-size: 1.1rem; position: relative; cursor: pointer; padding: 0.5rem; border-radius: 0.375rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
        .action-button:hover { color: var(--accent-color); background-color: rgba(49, 183, 209, 0.1); }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: var(--accent-color); color: white; font-size: 0.7rem; min-width: 16px; height: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }
        .logout-button { color: #8A9CC9; background: transparent; border: none; padding: 0.5rem; border-radius: 0.375rem; transition: all 0.2s; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
        .logout-button:hover { color: var(--accent-color); background-color: rgba(74, 71, 71, 0.14); }
        .main-container { margin-left: var(--sidebar-width-collapsed); padding-top: var(--top-nav-height); padding-bottom: var(--footer-height); flex: 1; min-height: 100vh; transition: margin-left 0.3s ease; overflow-y: auto; }
        body.menu-expanded .main-container { margin-left: var(--sidebar-width-expanded); }
        .content { padding: var(--content-padding); min-height: calc(100vh - var(--top-nav-height) - var(--footer-height)); }
        .footer { background-color: var(--primary-bg); border-top: 1px solid var(--border-color); padding: 1rem; position: fixed; bottom: 0; right: 0; left: var(--sidebar-width-collapsed); z-index: 999; transition: left 0.3s ease; height: var(--footer-height); display: flex; align-items: center; justify-content: center; }
        body.menu-expanded .footer { left: var(--sidebar-width-expanded); }
        .footer p { color: #8A9CC9; font-size: 0.875rem; margin: 0; }
        .footer span { color: var(--text-color); margin: 0 0.5rem; }
        .card { background-color: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: var(--section-margin); overflow: hidden; transition: transform var(--transition-speed), box-shadow var(--transition-speed); }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
        .card-header { background-color: rgba(49, 183, 209, 0.1); border-bottom: 1px solid var(--border-color); padding: 1.25rem; color: var(--text-color); font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .card-header i { color: var(--accent-color); font-size: 1.2rem; }
        .card-body { padding: var(--card-padding); }
        .table { background-color: transparent; color: var(--text-color); margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .table th { background-color: rgba(49, 183, 209, 0.1); border-color: var(--border-color); color: var(--text-color); font-weight: 600; padding: 1rem; white-space: nowrap; }
        .table td { border-color: var(--border-color); color: var(--text-color); padding: 1rem; vertical-align: middle; }
        .table tbody tr { transition: background-color var(--transition-speed); }
        .table tbody tr:hover { background-color: rgba(49, 183, 209, 0.05); }
        .btn { border-radius: 0.375rem; font-weight: 500; padding: 0.75rem 1.5rem; transition: all var(--transition-speed); display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-primary { background-color: var(--accent-color); border-color: var(--accent-color); color: white; }
        .btn-primary:hover { background-color: #2a9fb5; border-color: #2a9fb5; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(49, 183, 209, 0.2); }
        .btn-primary:active { transform: translateY(0); }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #218838; border-color: #218838; }
        .btn-secondary { background-color: var(--text-muted); border-color: var(--text-muted); }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .alert { border: none; border-radius: 0.5rem; margin-bottom: var(--spacing-md); padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .alert-info { background-color: rgba(49, 183, 209, 0.1); color: var(--accent-color); border-left: 4px solid var(--accent-color); }
        .alert-success { background-color: rgba(40, 167, 69, 0.1); color: #28a745; border-left: 4px solid #28a745; }
        .alert-warning { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; border-left: 4px solid #ffc107; }
        .alert-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border-left: 4px solid #dc3545; }
        .badge { padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.25rem; }
        .badge-primary { background-color: var(--accent-color); color: white; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-secondary { background-color: var(--text-muted); color: white; }
        .form-control { background-color: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-color); padding: 0.75rem 1rem; border-radius: 0.375rem; transition: all var(--transition-speed); }
        .form-control:focus { background-color: var(--secondary-bg); border-color: var(--accent-color); color: var(--text-color); box-shadow: 0 0 0 0.2rem rgba(49, 183, 209, 0.25); }
        .form-label { color: var(--text-color); font-weight: 500; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-label i { color: var(--accent-color); }
        .form-select { background-color: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-color); padding: 0.75rem 1rem; border-radius: 0.375rem; transition: all var(--transition-speed); }
        .form-select:focus { background-color: var(--secondary-bg); border-color: var(--accent-color); color: var(--text-color); box-shadow: 0 0 0 0.2rem rgba(49, 183, 209, 0.25); }
        .custom-file-input { position: relative; display: inline-block; width: 100%; }
        .custom-file-input input[type="file"] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
        .custom-file-label { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background-color: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 0.375rem; color: var(--text-color); cursor: pointer; transition: all var(--transition-speed); }
        .custom-file-label:hover { border-color: var(--accent-color); }
        .custom-file-label i { color: var(--accent-color); }
        @media (max-width: 768px) { .sidebar { width: 0; transform: translateX(-100%); } body.menu-expanded .sidebar { width: var(--sidebar-width-expanded); transform: translateX(0); } .top-nav { left: 0; } body.menu-expanded .top-nav { left: var(--sidebar-width-expanded); } .main-container { margin-left: 0; padding-left: 15px; padding-right: 15px; } body.menu-expanded .main-container { margin-left: var(--sidebar-width-expanded); } .footer { left: 0; } body.menu-expanded .footer { left: var(--sidebar-width-expanded); } :root { --content-padding: var(--spacing-sm); --section-margin: var(--spacing-sm); --card-padding: var(--spacing-sm); } }
        .loading { position: relative; }
        .loading::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; border-radius: 0.375rem; }
        .loading::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 2rem; height: 2rem; border: 3px solid var(--accent-color); border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; z-index: 1; }
        @keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-group .btn {
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all var(--transition-speed);
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-group .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-group .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }
        
        .btn-group .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-group .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn-group .btn i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="menu-items">
            <div class="menu-items-top">
                <a href="dashboard.php" class="menu-item" data-section="dashboard">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Tableau de bord</span>
                </a>
              
                <a href="gestion_notes.php" class="menu-item active" data-section="notes">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">Gestion des Notes</span>
                </a>
                <a href="historique.php" class="menu-item" data-section="historique">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Historique</span>
                </a>
            </div>
            <div class="menu-items-bottom">
                <a href="profil.php" class="menu-item" data-section="profile">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">Mon profil</span>
                </a>
            </div>
        </div>
    </div>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container-fluid">
            <div class="user-menu">
                <div class="action-button">
                    <i class="fas fa-bell"></i>
                </div>
                <a href="profil.php" class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($vacataire['prenom'], 0, 1) . substr($vacataire['nom'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($vacataire['prenom'] . ' ' . $vacataire['nom']); ?></span>
                        <span class="user-role"><?php echo ucfirst($vacataire['role']); ?></span>
                    </div>
                </a>
                <a href="logout.php" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>
    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-upload me-2"></i>
                                Upload des fichiers de notes
                            </div>
                            <div class="card-body">
                                <?php if ($message): ?>
                                    <div class="alert alert-success" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= htmlspecialchars($message) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($modules_affectes)): ?>
                                    <div class="alert alert-warning" role="alert">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Aucun module ne vous est affecté. Contactez l'administration.
                                    </div>
                                <?php else: ?>
                                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                        <div class="mb-3">
                                            <label for="module_id" class="form-label">
                                                <i class="fas fa-book me-1"></i>
                                                Module
                                            </label>
                                            <select name="module_id" id="module_id" class="form-select" required>
                                                <option value="">-- Sélectionnez un module --</option>
                                                <?php foreach ($modules_affectes as $mod): ?>
                                                    <option value="<?= $mod['id'] ?>">
                                                        <?= htmlspecialchars($mod['intitule']) ?> (<?= htmlspecialchars($mod['code']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="session_type" class="form-label">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Type de session
                                            </label>
                                            <select name="session_type" id="session_type" class="form-select" required>
                                                <option value="">-- Sélectionnez un type --</option>
                                                <option value="normale">Session normale</option>
                                                <option value="rattrapage">Session de rattrapage</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes_file" class="form-label">
                                                <i class="fas fa-file-excel"></i>
                                                Fichier Excel/CSV (.xlsx, .xls, .csv)
                                            </label>
                                            <div class="custom-file-input">
                                                <input type="file" name="notes_file" id="notes_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                                <label for="notes_file" class="custom-file-label">
                                                    <i class="fas fa-upload"></i>
                                                    <span>Choisir un fichier</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="fas fa-upload me-2"></i>
                                                Uploader le fichier
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($historique_fichiers)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <i class="fas fa-history me-2"></i>
                                    Historique de vos uploads
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Fichier</th>
                                                    <th>Module</th>
                                                    <th>Session</th>
                                                    <th>Statut</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historique_fichiers as $fichier): ?>
                                                    <tr>
                                                        <td>
                                                            <i class="fas fa-file-excel text-success me-1"></i>
                                                            <?= htmlspecialchars($fichier['nom_fichier']) ?>
                                                        </td>
                                                        <td>
                                                            <?= htmlspecialchars($fichier['nom_module']) ?>
                                                            <small class="text-muted">(<?= htmlspecialchars($fichier['code']) ?>)</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= $fichier['type_session'] == 'normale' ? 'badge-primary' : 'badge-warning' ?>">
                                                                <?= ucfirst($fichier['type_session']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statut_class = match($fichier['statut']) {
                                                                'non_traite' => 'badge-secondary',
                                                                'en_cours' => 'badge-info',
                                                                'traite' => 'badge-success',
                                                                'erreur' => 'badge-danger',
                                                                default => 'badge-secondary'
                                                            };
                                                            ?>
                                                            <span class="badge <?= $statut_class ?>">
                                                                <?= ucfirst(str_replace('_', ' ', $fichier['statut'])) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small><?= date('d/m/Y H:i', strtotime($fichier['date_upload'])) ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="download.php?type=excel&id=<?= $fichier['id'] ?>" 
                                                                   class="btn btn-sm btn-success" 
                                                                   title="Télécharger en Excel">
                                                                    <i class="fas fa-file-excel"></i>
                                                                </a>
                                                                <a href="download.php?type=pdf&id=<?= $fichier['id'] ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   title="Télécharger en PDF">
                                                                    <i class="fas fa-file-pdf"></i>
                                                                </a>
                                                                <a href="voir_notes.php?fichier=<?= $fichier['id'] ?>" 
                                                                   class="btn btn-sm btn-info" 
                                                                   title="Visualiser le contenu">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>
                &copy; 2025 Système de Gestion des Affectations - ENSA Al Hoceima
                <span>|</span>
                <span style="color: #8A9CC9;">Version 1.0.0</span>
            </p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle functionality
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('menu-expanded');
                    localStorage.setItem('menuExpanded', document.body.classList.contains('menu-expanded'));
                });
            }
            // Load saved menu state
            const savedMenuState = localStorage.getItem('menuExpanded');
            if (savedMenuState === 'true') {
                document.body.classList.add('menu-expanded');
            }
            // Logout functionality
            const logoutButton = document.querySelector('.logout-button');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                        window.location.href = 'logout.php';
                    }
                });
            }
            // Prévenir la double soumission du formulaire
            const uploadForm = document.getElementById('uploadForm');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function() {
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Upload en cours...';
                    }
                });
            }
            // Gestion de l'affichage du nom du fichier sélectionné
            const fileInput = document.getElementById('notes_file');
            const fileLabel = fileInput.nextElementSibling;
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    fileLabel.querySelector('span').textContent = this.files[0].name;
                } else {
                    fileLabel.querySelector('span').textContent = 'Choisir un fichier';
                }
            });
        });
    </script>
</body>
</html> 