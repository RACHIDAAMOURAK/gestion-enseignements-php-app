<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un vacataire
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vacataire') {
    // Rediriger vers la page de connexion
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/config/database.php';

// Récupération des informations du vacataire
$vacataire_id = $_SESSION['user_id'];
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
        sp.nom as specialite_nom,
        u.date_creation,
        u.derniere_connexion
    FROM utilisateurs u
    LEFT JOIN departements d ON u.id_departement = d.id
    LEFT JOIN utilisateur_specialites us ON u.id = us.id_utilisateur
    LEFT JOIN specialites sp ON us.id_specialite = sp.id
    WHERE u.id = ? AND u.role = 'vacataire' AND u.actif = 1
");
$stmt->execute([$vacataire_id]);
$vacataire = $stmt->fetch();

if (!$vacataire) {
    $_SESSION['error_message'] = "Accès non autorisé ou compte inactif.";
    header('Location: ../login.php');
    exit();
}

// Mise à jour de la dernière connexion
$stmt = $pdo->prepare("
    UPDATE utilisateurs 
    SET derniere_connexion = CURRENT_TIMESTAMP 
    WHERE id = ? AND role = 'vacataire'
");
$stmt->execute([$vacataire_id]);

// Récupération des modules affectés
$stmt = $pdo->prepare("
    SELECT 
        h.*,
        ue.id as module_id,
        ue.code,
        ue.intitule,
        ue.volume_horaire_cm,
        ue.volume_horaire_td,
        ue.volume_horaire_tp,
        ue.semestre,
        f.nom as filiere_nom,
        d.nom as departement_nom,
        h.date_affectation,
        h.type_cours
    FROM historique_affectations_vacataire h
    JOIN unites_enseignement ue ON h.id_unite_enseignement = ue.id
    LEFT JOIN filieres f ON ue.id_filiere = f.id
    LEFT JOIN departements d ON ue.id_departement = d.id
    WHERE h.id_vacataire = ?
    AND h.action = 'affectation'
    ORDER BY h.date_affectation DESC
");
$stmt->execute([$vacataire_id]);
$modules = $stmt->fetchAll();

// Récupération des notes en attente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM fichiers_notes fn
    JOIN historique_affectations_vacataire hav ON fn.id_unite_enseignement = hav.id_unite_enseignement
    WHERE hav.id_vacataire = ? 
    AND fn.statut = 'en_attente'
");
$stmt->execute([$vacataire_id]);
$notes_en_attente = $stmt->fetch()['count'];

// Récupération du volume horaire total
$stmt = $pdo->prepare("
    SELECT SUM(
        CASE h.type_cours
            WHEN 'CM' THEN ue.volume_horaire_cm
            WHEN 'TD' THEN ue.volume_horaire_td
            WHEN 'TP' THEN ue.volume_horaire_tp
            ELSE 0
        END
    ) as total
    FROM historique_affectations_vacataire h
    JOIN unites_enseignement ue ON h.id_unite_enseignement = ue.id
    WHERE h.id_vacataire = ?
    AND h.action = 'affectation'
");
$stmt->execute([$vacataire_id]);
$volume_horaire_total = $stmt->fetch()['total'] ?? 0;

// Récupération des tâches complétées
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM fichiers_notes fn
    JOIN historique_affectations_vacataire hav ON fn.id_unite_enseignement = hav.id_unite_enseignement
    WHERE hav.id_vacataire = ? 
    AND fn.statut = 'valide'
");
$stmt->execute([$vacataire_id]);
$taches_completees = $stmt->fetch()['count'];

// Récupération du nombre total de modules
$nombre_modules = count($modules);

// Récupération de l'historique des fichiers uploadés par le vacataire
$stmt = $pdo->prepare('
    SELECT fn.*, ue.intitule, ue.code
    FROM fichiers_notes fn
    JOIN unites_enseignement ue ON fn.id_unite_enseignement = ue.id
    WHERE fn.id_enseignant = ?
    ORDER BY fn.date_upload DESC
');
$stmt->execute([$vacataire_id]);
$fichiers_uploades = $stmt->fetchAll();

// Construire une liste unique des modules par module_id
$modules_uniques = [];
foreach ($modules as $module) {
    $id = $module['module_id'] ?? $module['id'];
    if (!isset($modules_uniques[$id])) {
        $modules_uniques[$id] = $module;
    }
}

// Traitement de l'upload des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_notes':
                $module_id = $_POST['module_id'];
                $session_type = $_POST['session_type'];
                // On utilise l'ID du vacataire comme id_enseignant pour l'upload des notes
                $id_enseignant = $vacataire_id;
                // Vérification que le module appartient bien au vacataire
                $stmt = $pdo->prepare("
                    SELECT id_unite_enseignement 
                    FROM historique_affectations_vacataire 
                    WHERE id_unite_enseignement = ? AND id_vacataire = ?
                ");
                $stmt->execute([$module_id, $vacataire_id]);
                $affectation = $stmt->fetch();
                if (!$affectation) {
                    echo '<pre style="color:orange">DEBUG: module_id=' . htmlspecialchars($module_id) . ', vacataire_id=' . htmlspecialchars($vacataire_id) . ' => pas trouvé dans historique_affectations_vacataire</pre>';
                    $_SESSION['error_message'] = "Module non autorisé.";
                    break;
                }
                if (isset($_FILES['notes_file']) && $_FILES['notes_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['notes_file'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    // Vérification de la taille du fichier (max 5MB)
                    if ($file['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = "Le fichier est trop volumineux. Taille maximale : 5MB.";
                    }
                    // Vérification de l'extension
                    else if (in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
                        $upload_dir = '../uploads/notes/';
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0755, true)) {
                                $_SESSION['error_message'] = "Erreur lors de la création du dossier d'upload.";
                                break;
                            }
                        }
                        $file_name = uniqid() . '_' . $file['name'];
                        $file_path = $upload_dir . $file_name;
                        if (move_uploaded_file($file['tmp_name'], $file_path)) {
                            try {
                                // On enregistre bien l'ID du vacataire dans id_enseignant
                                $stmt = $pdo->prepare("
                                    INSERT INTO fichiers_notes (
                                        id_unite_enseignement, 
                                        id_enseignant, 
                                        type_session, 
                                        nom_fichier, 
                                        chemin_fichier, 
                                        date_upload, 
                                        statut
                                    ) VALUES (?, ?, ?, ?, ?, NOW(), 'en_attente')
                                ");
                                $stmt->execute([
                                    $module_id, 
                                    $id_enseignant, 
                                    $session_type, 
                                    $file['name'], 
                                    $file_path
                                ]);
                                // Debug temporaire : afficher les valeurs envoyées à l'INSERT
                                echo '<pre>module_id=' . $module_id . ', id_enseignant=' . $id_enseignant . ', session_type=' . $session_type . ', nom_fichier=' . $file['name'] . ', chemin_fichier=' . $file_path . '</pre>';
                                $_SESSION['success_message'] = "Les notes ont été uploadées avec succès.";
                            } catch (PDOException $e) {
                                unlink($file_path); // Supprimer le fichier en cas d'erreur
                                $_SESSION['error_message'] = "Erreur lors de l'enregistrement des notes : " . $e->getMessage();
                                echo '<pre>Erreur SQL : ' . $e->getMessage() . '</pre>';
                            }
                        } else {
                            $_SESSION['error_message'] = "Erreur lors de l'upload du fichier. Vérifiez les permissions du dossier.";
                        }
                    } else {
                        $_SESSION['error_message'] = "Format de fichier non autorisé. Utilisez Excel ou CSV.";
                    }
                } else {
                    $error_message = "Veuillez sélectionner un fichier.";
                    if (isset($_FILES['notes_file'])) {
                        switch ($_FILES['notes_file']['error']) {
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                $error_message = "Le fichier est trop volumineux.";
                                break;
                            case UPLOAD_ERR_PARTIAL:
                                $error_message = "Le fichier n'a été que partiellement uploadé.";
                                break;
                            case UPLOAD_ERR_NO_FILE:
                                $error_message = "Aucun fichier n'a été uploadé.";
                                break;
                            case UPLOAD_ERR_NO_TMP_DIR:
                                $error_message = "Dossier temporaire manquant.";
                                break;
                            case UPLOAD_ERR_CANT_WRITE:
                                $error_message = "Échec de l'écriture du fichier sur le disque.";
                                break;
                            case UPLOAD_ERR_EXTENSION:
                                $error_message = "Une extension PHP a arrêté l'upload du fichier.";
                                break;
                        }
                    }
                    $_SESSION['error_message'] = $error_message;
                }
                break;
                
            case 'update_profile':
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $email = trim($_POST['email']);
                $specialite = isset($_POST['specialite']) ? trim($_POST['specialite']) : null;
                
                // Validation des champs
                $errors = [];
                if (empty($nom)) $errors[] = "Le nom est requis.";
                if (empty($prenom)) $errors[] = "Le prénom est requis.";
                if (empty($email)) $errors[] = "L'email est requis.";
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide.";
                
                if (empty($errors)) {
                    try {
                        $pdo->beginTransaction();
                        
                        // Vérification si l'email existe déjà
                        $stmt = $pdo->prepare("
                            SELECT id FROM utilisateurs 
                            WHERE email = ? AND id != ? AND role = 'vacataire'
                        ");
                        $stmt->execute([$email, $vacataire_id]);
                        if ($stmt->fetch()) {
                            throw new Exception("Cet email est déjà utilisé par un autre vacataire.");
                        }
                        
                        // Mise à jour des informations utilisateur
                        $stmt = $pdo->prepare("
                            UPDATE utilisateurs 
                            SET nom = ?, prenom = ?, email = ?
                            WHERE id = ? AND role = 'vacataire'
                        ");
                        $stmt->execute([$nom, $prenom, $email, $vacataire_id]);
                        
                        // Mise à jour de la spécialité si fournie
                        if ($specialite) {
                            $stmt = $pdo->prepare("DELETE FROM utilisateur_specialites WHERE id_utilisateur = ?");
                            $stmt->execute([$vacataire_id]);
                            
                            $stmt = $pdo->prepare("INSERT INTO utilisateur_specialites (id_utilisateur, id_specialite) VALUES (?, ?)");
                            $stmt->execute([$vacataire_id, $specialite]);
                        }
                        
                        $pdo->commit();
                        $_SESSION['success_message'] = "Profil mis à jour avec succès.";
                    } catch(Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error_message'] = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
                    }
                } else {
                    $_SESSION['error_message'] = implode("<br>", $errors);
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validation des mots de passe
                $errors = [];
                if (empty($current_password)) $errors[] = "Le mot de passe actuel est requis.";
                if (empty($new_password)) $errors[] = "Le nouveau mot de passe est requis.";
                if (strlen($new_password) < 8) $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
                if ($new_password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas.";
                
                if (empty($errors)) {
                    try {
                        // Vérification du mot de passe actuel
                        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ? AND role = 'vacataire'");
                        $stmt->execute([$vacataire_id]);
                        $user = $stmt->fetch();
                        
                        if ($user && password_verify($current_password, $user['mot_de_passe'])) {
                            // Hashage du nouveau mot de passe
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            // Mise à jour du mot de passe
                            $stmt = $pdo->prepare("
                                UPDATE utilisateurs 
                                SET mot_de_passe = ?, date_changement_mdp = NOW()
                                WHERE id = ? AND role = 'vacataire'
                            ");
                            $stmt->execute([$hashed_password, $vacataire_id]);
                            
                            $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
                        } else {
                            $_SESSION['error_message'] = "Le mot de passe actuel est incorrect.";
                        }
                    } catch(PDOException $e) {
                        $_SESSION['error_message'] = "Erreur lors du changement de mot de passe : " . $e->getMessage();
                    }
                } else {
                    $_SESSION['error_message'] = implode("<br>", $errors);
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Affichage des messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Affichage des erreurs PHP (debug temporaire)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Vacataire - Système de Gestion</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width-collapsed);
            background-color: var(--primary-bg);
            border-right: 1px solid var(--border-color);
            transition: width 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        body.menu-expanded .sidebar {
            width: var(--sidebar-width-expanded);
        }

        .sidebar-logo {
            padding: 1.5rem 0;
            color: var(--accent-color);
            text-align: center;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-items {
            display: flex;
            flex-direction: column;
            height: calc(100% - 60px);
            padding: 1rem 0;
            justify-content: space-between;
        }

        .menu-items-top {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-items-bottom {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-item {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8A9CC9;
        }

        .menu-toggle {
            color: var(--accent-color);
            text-align: center;
            padding: 1.2rem 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            margin-top: 0.5rem;
            width: 100%;
        }

        .menu-toggle i {
            font-size: 1.5rem;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-toggle:hover i {
            transform: rotate(180deg);
        }

        body.menu-expanded .menu-toggle i {
            transform: rotate(-180deg);
        }

        body.menu-expanded .menu-toggle:hover i {
            transform: rotate(-360deg);
        }

        .menu-toggle .menu-text {
            display: none;
        }

        body.menu-expanded .menu-toggle {
            justify-content: flex-start;
            padding-left: 1.5rem;
        }

        .menu-item:hover, .menu-item.active {
            color: var(--text-color);
            background-color: rgba(49, 183, 209, 0.1);
        }

        .menu-item.active {
            background-color: var(--secondary-bg);
            border-left: 3px solid var(--accent-color);
        }

        .menu-item:hover i, .menu-item.active i {
            color: var(--accent-color) !important;
        }

        .menu-text {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            font-size: 0.95rem;
        }

        body.menu-expanded .menu-text {
            opacity: 1;
            visibility: visible;
        }

        .top-nav {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width-collapsed);
            z-index: 999;
            height: var(--top-nav-height);
            background-color: var(--primary-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 var(--spacing-md);
            transition: left 0.3s ease;
        }

        body.menu-expanded .top-nav {
            left: var(--sidebar-width-expanded);
        }

        .container-fluid {
            width: 100%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            justify-content: flex-end;
            width: 100%;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .user-profile:hover {
            background-color: rgba(49, 183, 209, 0.1);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            color: var(--text-color);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .user-role {
            color: #8A9CC9;
            font-size: 0.75rem;
        }

        .action-button {
            color: #8A9CC9;
            font-size: 1.1rem;
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .action-button:hover {
            color: var(--accent-color);
            background-color: rgba(49, 183, 209, 0.1);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .logout-button {
            color: #8A9CC9;
            background: transparent;
            border: none;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .logout-button:hover {
            color: var(--accent-color);
            background-color: rgba(74, 71, 71, 0.14);
        }

        .main-container {
            margin-left: var(--sidebar-width-collapsed);
            padding-top: var(--top-nav-height);
            padding-bottom: var(--footer-height);
            flex: 1;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            overflow-y: auto;
        }

        body.menu-expanded .main-container {
            margin-left: var(--sidebar-width-expanded);
        }

        .content {
            padding: var(--content-padding);
            min-height: calc(100vh - var(--top-nav-height) - var(--footer-height));
        }

        .footer {
            background-color: var(--primary-bg);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            position: fixed;
            bottom: 0;
            right: 0;
            left: var(--sidebar-width-collapsed);
            z-index: 999;
            transition: left 0.3s ease;
            height: var(--footer-height);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.menu-expanded .footer {
            left: var(--sidebar-width-expanded);
        }

        .footer p {
            color: #8A9CC9;
            font-size: 0.875rem;
            margin: 0;
        }

        .footer span {
            color: var(--text-color);
            margin: 0 0.5rem;
        }

        /* Styles pour les cartes */
        .card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: var(--section-margin);
            overflow: hidden;
        }

        .card-header {
            background-color: rgba(49, 183, 209, 0.1);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .card-body {
            padding: var(--card-padding);
        }

        /* Styles pour les tableaux */
        .table {
            background-color: transparent;
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table th {
            background-color: rgba(49, 183, 209, 0.1);
            border-color: var(--border-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 0.75rem;
        }

        .table td {
            border-color: var(--border-color);
            color: var(--text-color);
            padding: 0.75rem;
        }

        /* Styles pour les boutons */
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a9fb5;
            border-color: #2a9fb5;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }

        .btn-secondary {
            background-color: var(--text-muted);
            border-color: var(--text-muted);
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Styles pour les alertes */
        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: var(--spacing-md);
        }

        .alert-info {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
            border-left: 4px solid var(--accent-color);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
        }

        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border-left: 4px solid #ffc107;
        }

        /* Styles pour les badges */
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        /* Styles pour les formulaires */
        .form-control {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--secondary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.2rem rgba(49, 183, 209, 0.25);
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Styles pour les statistiques */
        .stat-card {
            background: linear-gradient(135deg, var(--secondary-bg) 0%, rgba(49, 183, 209, 0.1) 100%);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            body.menu-expanded .sidebar {
                width: var(--sidebar-width-expanded);
                transform: translateX(0);
            }

            .top-nav {
                left: 0;
            }

            body.menu-expanded .top-nav {
                left: var(--sidebar-width-expanded);
            }

            .main-container {
                margin-left: 0;
                padding-left: 15px;
                padding-right: 15px;
            }

            body.menu-expanded .main-container {
                margin-left: var(--sidebar-width-expanded);
            }

            .footer {
                left: 0;
            }

            body.menu-expanded .footer {
                left: var(--sidebar-width-expanded);
            }

            :root {
                --content-padding: var(--spacing-sm);
                --section-margin: var(--spacing-sm);
                --card-padding: var(--spacing-sm);
            }
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
                <div class="menu-item active" data-section="dashboard">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Tableau de bord</span>
                </div>
                
                
                <a href="gestion_notes.php" class="menu-item" data-section="notes" onclick="window.location.href='gestion_notes.php'; return false;">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">Gestion des Notes</span>
                </a>
                
                <a href="historique.php" class="menu-item" data-section="historique" onclick="window.location.href='historique.php'; return false;">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Historique</span>
                </a>
            </div>

            <div class="menu-items-bottom">
                <a href="profil.php" class="menu-item" data-section="profile" onclick="window.location.href='profil.php'; return false;">
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
                    <?php if (isset($notifications_count) && $notifications_count > 0): ?>
                    <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </div>
                <a href="#" class="user-profile" onclick="showProfileSection(); return false;">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($vacataire['prenom'], 0, 1) . substr($vacataire['nom'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($vacataire['prenom'] . ' ' . $vacataire['nom']); ?></span>
                        <span class="user-role"><?php echo ucfirst($vacataire['role']); ?></span>
                    </div>
                </a>
                <a href="#" class="logout-button" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <div class="container-fluid">
                
                <!-- Section Tableau de bord -->
                <div id="dashboard-section" class="content-section">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-white mb-3">
                                <i class="fas fa-home me-2" style="color: var(--accent-color);"></i>
                                Tableau de bord - Vacataire
                            </h2>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Bienvenue dans votre espace vacataire. Vous pouvez consulter vos modules affectés et gérer les notes.
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $nombre_modules; ?></div>
                                <div class="stat-label">Modules Affectés</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $volume_horaire_total; ?></div>
                                <div class="stat-label">Volume Horaire Total</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $notes_en_attente; ?></div>
                                <div class="stat-label">Notes en Attente</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $taches_completees; ?></div>
                                <div class="stat-label">Tâches Complétées</div>
                            </div>
                        </div>
                    </div>

                    <!-- Modules récents -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-book me-2"></i>
                                    Mes Modules - Aperçu
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Module</th>
                                                    <th>Filière</th>
                                                    <th>Type</th>
                                                    <th>Volume Horaire</th>
                                                    <th>Statut Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($modules as $module): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($module['intitule'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($module['filiere_nom'] ?? 'Non spécifié'); ?></td>
                                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($module['type_cours'] ?? ''); ?></span></td>
                                                    <td>
                                                        <?php
                                                        $type = $module['type_cours'] ?? '';
                                                        $volume = 0;
                                                        if ($type === 'CM') $volume = $module['volume_horaire_cm'] ?? 0;
                                                        elseif ($type === 'TD') $volume = $module['volume_horaire_td'] ?? 0;
                                                        elseif ($type === 'TP') $volume = $module['volume_horaire_tp'] ?? 0;
                                                        echo $volume . 'h';
                                                        ?>
                                                    </td>
                                                    <td><span class="badge badge-success">Validées</span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Profil -->
                <div id="profile-section" class="content-section" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-white mb-3">
                                <i class="fas fa-user me-2" style="color: var(--accent-color);"></i>
                                Mon Profil
                            </h2>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-user-circle me-2"></i>
                                    Informations Personnelles
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle bg-primary text-white">
                                                <?php echo strtoupper(substr($vacataire['prenom'], 0, 1) . substr($vacataire['nom'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($vacataire['prenom'] . ' ' . $vacataire['nom']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($vacataire['role']); ?></p>
                                        </div>
                                    </div>
                                    <div class="profile-info">
                                        <div class="mb-3">
                                            <strong>Nom d'utilisateur:</strong>
                                            <p class="text-muted"><?php echo htmlspecialchars($vacataire['nom_utilisateur']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Email:</strong>
                                            <p class="text-muted"><?php echo htmlspecialchars($vacataire['email']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Spécialité:</strong>
                                            <p class="text-muted"><?php echo htmlspecialchars($vacataire['specialite_nom'] ?? $vacataire['specialite']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Département:</strong>
                                            <p class="text-muted"><?php echo htmlspecialchars($vacataire['departement_nom'] ?? 'Non assigné'); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Date d'inscription:</strong>
                                            <p class="text-muted"><?php echo date('d/m/Y', strtotime($vacataire['date_creation'])); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Dernière connexion:</strong>
                                            <p class="text-muted"><?php echo $vacataire['derniere_connexion'] ? date('d/m/Y H:i', strtotime($vacataire['derniere_connexion'])) : 'Première connexion'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-edit me-2"></i>
                                    Modifier les Informations
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_profile">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Nom</label>
                                                    <input type="text" class="form-control" name="nom" value="<?php echo $vacataire['nom']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Prénom</label>
                                                    <input type="text" class="form-control" name="prenom" value="<?php echo $vacataire['prenom']; ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" name="email" value="<?php echo $vacataire['email']; ?>" required>
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Spécialité</label>
                                                    <select class="form-control" name="specialite" required>
                                                        <option value="<?php echo $vacataire['specialite']; ?>" selected><?php echo $vacataire['specialite']; ?></option>
                                                        <option value="informatique">Informatique</option>
                                                        <option value="mathematiques">Mathématiques</option>
                                                        <option value="physique">Physique</option>
                                                        <option value="chimie">Chimie</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                      
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Changer le mot de passe -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <i class="fas fa-lock me-2"></i>
                                    Changer le Mot de Passe
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="change_password">
                                        <div class="mb-3">
                                            <label class="form-label">Mot de passe actuel</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nouveau mot de passe</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirmer le nouveau mot de passe</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-key me-2"></i>Changer le mot de passe
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
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
            const menuItems = document.querySelectorAll('.menu-item');
            const contentSections = document.querySelectorAll('.content-section');
            
            if (!menuToggle) {
                console.error("Élément menu-toggle non trouvé!");
                return;
            }
            
            // Load saved menu state
            const savedMenuState = localStorage.getItem('menuExpanded');
            if (savedMenuState === 'true') {
                document.body.classList.add('menu-expanded');
            }
            
            // Toggle menu function
            function toggleMenu(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                const isExpanded = document.body.classList.toggle('menu-expanded');
                localStorage.setItem('menuExpanded', isExpanded);
            }
            
            // Close menu function
            function closeMenu() {
                document.body.classList.remove('menu-expanded');
                localStorage.setItem('menuExpanded', false);
            }
            
            // Menu toggle event
            menuToggle.addEventListener('click', toggleMenu);
            
            // Prevent menu close when clicking on sidebar
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (document.body.classList.contains('menu-expanded') && 
                    sidebar && !sidebar.contains(e.target) && 
                    e.target !== menuToggle) {
                    closeMenu();
                }
            });

            // Navigation functionality
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all menu items
                    menuItems.forEach(mi => mi.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Hide all content sections
                    contentSections.forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Show corresponding section
                    const sectionId = this.getAttribute('data-section') + '-section';
                    const targetSection = document.getElementById(sectionId);
                    if (targetSection) {
                        targetSection.style.display = 'block';
                    }
                });
            });

            // Form submissions (demo)
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show success message (demo)
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success';
                    successAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>Action effectuée avec succès!';
                    
                    // Insert alert at the top of the form
                    form.insertBefore(successAlert, form.firstChild);
                    
                    // Remove alert after 3 seconds
                    setTimeout(() => {
                        successAlert.remove();
                    }, 3000);
                });
            });

            // File upload simulation
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const fileName = this.files[0].name;
                        const fileInfo = document.createElement('small');
                        fileInfo.className = 'text-info';
                        fileInfo.textContent = `Fichier sélectionné: ${fileName}`;
                        
                        // Remove any existing file info
                        const existingInfo = this.parentNode.querySelector('.text-info');
                        if (existingInfo) {
                            existingInfo.remove();
                        }
                        
                        // Add new file info
                        this.parentNode.appendChild(fileInfo);
                    }
                });
            });

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

            // Notification bell functionality
            const notificationBell = document.querySelector('.action-button');
            if (notificationBell) {
                notificationBell.addEventListener('click', function() {
                    alert('Vous avez 2 nouvelles notifications:\n1. Notes de MAT105 en attente de validation\n2. Nouveau module affecté: INF401');
                });
            }

            // Table row hover effects
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(49, 183, 209, 0.05)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            // Stat cards hover animation
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                    this.style.boxShadow = '0 10px 25px rgba(49, 183, 209, 0.2)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '';
                });
            });

            // Function to show profile section
            window.showProfileSection = function() {
                // Remove active class from all menu items
                menuItems.forEach(mi => mi.classList.remove('active'));
                
                // Add active class to profile menu item
                const profileMenuItem = document.querySelector('.menu-item[data-section="profile"]');
                if (profileMenuItem) {
                    profileMenuItem.classList.add('active');
                }
                
                // Hide all content sections
                contentSections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show profile section
                const profileSection = document.getElementById('profile-section');
                if (profileSection) {
                    profileSection.style.display = 'block';
                }
            };

            // Fonction globale pour la modale d'upload de notes
            window.showUploadNotesModal = function(moduleId, moduleName) {
                document.getElementById('modal_module_id').value = moduleId;
                document.getElementById('modal_module_name').value = moduleName;
                var modal = new bootstrap.Modal(document.getElementById('uploadNotesModal'));
                modal.show();
            }

            // Gestion des clics sur les éléments du menu
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    const section = this.getAttribute('data-section');
                    if (section === 'notes') {
                        window.location.href = 'gestion_notes.php';
                        return;
                    }
                    // ... rest of the existing click handler code ...
                });
            });
        });
    </script>
</body>
</html>