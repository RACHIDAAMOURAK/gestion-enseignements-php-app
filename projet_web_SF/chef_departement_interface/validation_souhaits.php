<?php
session_start();
include 'db.php';
$pdo = connectDB();

// Récupérer l'ID du département depuis la session
$id_departement = $_SESSION['id_departement'];
$current_page = basename($_SERVER['PHP_SELF']);

// Récupération de tous les vœux des enseignants du département
$query = "SELECT vp.*, u.nom, u.prenom, u.role, ue.id as id_ue, ue.code, ue.intitule, 
          ue.volume_horaire_cm, ue.volume_horaire_td, ue.volume_horaire_tp,
          f.id as id_filiere, f.nom as nom_filiere, 
          d.id as id_departement, d.nom as nom_departement,
          ue.annee_universitaire, ue.semestre
          FROM voeux_professeurs vp
          JOIN utilisateurs u ON vp.id_utilisateur = u.id
          JOIN unites_enseignement ue ON vp.id_ue = ue.id
          JOIN filieres f ON vp.id_filiere = f.id
          JOIN departements d ON u.id_departement = d.id
          WHERE u.id_departement = ?
          ORDER BY u.nom, vp.priorite";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_departement]);
$voeux = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour journaliser une décision
function enregistrerDecision($pdo, $type_entite, $id_entite, $id_utilisateur_decision, $ancien_statut, $nouveau_statut, $commentaire) {
    // Log des paramètres reçus
    error_log("Enregistrement décision - Type: $type_entite, ID: $id_entite, Utilisateur: $id_utilisateur_decision");
    error_log("Statuts - Ancien: $ancien_statut, Nouveau: $nouveau_statut");
    error_log("Commentaire: $commentaire");

    // Vérifier que le type d'entité est valide
    $types_valides = ['voeux_professeurs', 'affectation', 'unite_vacante'];
    if (!in_array($type_entite, $types_valides)) {
        error_log("Type d'entité invalide: " . $type_entite);
        return false;
    }

    // Normaliser les statuts
    $ancien_statut = strtolower(trim($ancien_statut));
    $nouveau_statut = strtolower(trim($nouveau_statut));

    try {
    $stmt = $pdo->prepare("INSERT INTO journal_decisions 
                          (type_entite, id_entite, id_utilisateur_decision, ancien_statut, nouveau_statut, commentaire, date_decision)
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([
        $type_entite,
        $id_entite,
        $id_utilisateur_decision,
        $ancien_statut,
        $nouveau_statut,
        $commentaire
    ]);

        if (!$result) {
            $error = $stmt->errorInfo();
            error_log("Erreur lors de l'insertion dans journal_decisions: " . $error[2]);
            return false;
        }

        $id_decision = $pdo->lastInsertId();
        error_log("Décision enregistrée avec succès. ID: $id_decision");
        return true;
    } catch (PDOException $e) {
        error_log("Exception lors de l'insertion dans journal_decisions: " . $e->getMessage());
        return false;
    }
}

// Fonction pour ajouter une affectation à l'historique
function addToHistorique($pdo, $voeu_info, $commentaire) {
    $type_cours = trim($voeu_info['type_ue']);
    $volume_field = "volume_horaire_" . strtolower($type_cours);
    $volume_horaire = $voeu_info[$volume_field] ?? 0;
    
    $stmt = $pdo->prepare("INSERT INTO historique_affectations 
                         (id_utilisateur, role, 
                          id_unite_enseignement, code_ue, intitule_ue, 
                          id_filiere, nom_filiere, id_departement, nom_departement, 
                          annee_universitaire, semestre, type_cours, volume_horaire, 
                          commentaire_chef, statut, date_affectation)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $voeu_info['id_utilisateur'],
        $voeu_info['role'],
        $voeu_info['id_unite_enseignement'],
        $voeu_info['code_ue'],
        $voeu_info['intitule_ue'],
        $voeu_info['id_filiere'],
        $voeu_info['nom_filiere'],
        $voeu_info['id_departement'],
        $voeu_info['nom_departement'],
        $voeu_info['annee_universitaire'],
        $voeu_info['semestre'],
        $type_cours,
        $volume_horaire,
        $commentaire,
        'validé'
    ]);
}

// Traitement des affectations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id_voeu'])) {
        $action = $_POST['action'];
        $id_voeu = $_POST['id_voeu'];
        $commentaire = $_POST['commentaire'] ?? '';
        $id_utilisateur_decision = $_SESSION['user_id'];
        
        // Récupérer les informations du vœu
        $stmt = $pdo->prepare("SELECT vp.*, vp.statut as ancien_statut, u.id as id_utilisateur, u.nom, u.prenom, u.role,
                               ue.id as id_unite_enseignement, ue.code as code_ue, ue.intitule as intitule_ue, 
                               ue.volume_horaire_cm, ue.volume_horaire_td, ue.volume_horaire_tp,
                               ue.annee_universitaire, ue.semestre,
                               f.id as id_filiere, f.nom as nom_filiere,
                               d.id as id_departement, d.nom as nom_departement
                               FROM voeux_professeurs vp
                               JOIN utilisateurs u ON vp.id_utilisateur = u.id
                               JOIN unites_enseignement ue ON vp.id_ue = ue.id
                               JOIN filieres f ON vp.id_filiere = f.id
                               JOIN departements d ON u.id_departement = d.id
                               WHERE vp.id = ?");
        $stmt->execute([$id_voeu]);
        $voeu_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $ancien_statut = $voeu_info['ancien_statut'] ?? 'en_attente';
        
        if ($action === 'valider') {
            $nouveau_statut = 'validé';
            
            // Mettre à jour le statut du vœu à 'validé'
            $stmt = $pdo->prepare("UPDATE voeux_professeurs SET statut = 'validé', commentaire_chef = ? WHERE id = ?");
            $stmt->execute([$commentaire, $id_voeu]);
            
            // Enregistrer dans historique_affectations avec le type du vœu
            addToHistorique($pdo, $voeu_info, $commentaire);
            
            // Journaliser la décision
            $decision_enregistree = enregistrerDecision($pdo, 'voeux_professeurs', $id_voeu, $id_utilisateur_decision, $ancien_statut, $nouveau_statut, $commentaire);
            
            // Vérifier si la décision a été enregistrée
            if ($decision_enregistree) {
                $stmt = $pdo->prepare("SELECT * FROM journal_decisions WHERE type_entite = 'voeux_professeurs' AND id_entite = ? ORDER BY date_decision DESC LIMIT 1");
                $stmt->execute([$id_voeu]);
                $derniere_decision = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($derniere_decision) {
                    error_log("Décision vérifiée - ID: " . $derniere_decision['id'] . ", Statut: " . $derniere_decision['nouveau_statut']);
                } else {
                    error_log("ERREUR: La décision n'a pas été trouvée dans la base de données après l'enregistrement");
                }
            }
            
            $_SESSION['message'] = "Vœu validé avec succès (Type: " . $voeu_info['type_ue'] . ")";
        } 
        elseif ($action === 'rejeter') {
            $nouveau_statut = 'rejeté';
            
            // Supprimer les affectations correspondantes
            $stmt = $pdo->prepare("DELETE FROM historique_affectations 
                                  WHERE id_utilisateur = ? 
                                  AND id_unite_enseignement = ?");
            $stmt->execute([$voeu_info['id_utilisateur'], $voeu_info['id_unite_enseignement']]);
            
            // Mettre à jour le statut du vœu
            $stmt = $pdo->prepare("UPDATE voeux_professeurs SET statut = 'rejeté', commentaire_chef = ? WHERE id = ?");
            $stmt->execute([$commentaire, $id_voeu]);
            
            // Journaliser la décision
            enregistrerDecision($pdo, 'voeux_professeurs', $id_voeu, $id_utilisateur_decision, $ancien_statut, $nouveau_statut, $commentaire);
            
            $_SESSION['message'] = "Vœu rejeté avec succès";
        }
        elseif ($action === 'modifier') {
            $nouveau_statut = $_POST['new_status'];
            
            // Si on passe à 'rejeté' ou 'en_attente', supprimer les affectations
            if ($nouveau_statut === 'rejeté' || $nouveau_statut === 'en_attente') {
                $stmt = $pdo->prepare("DELETE FROM historique_affectations 
                                      WHERE id_utilisateur = ? 
                                      AND id_unite_enseignement = ?");
                $stmt->execute([$voeu_info['id_utilisateur'], $voeu_info['id_unite_enseignement']]);
                $message_suffix = " et affectations supprimées";
            }
            // Si on passe à 'validé' depuis un autre statut, ajouter à l'historique
            elseif ($nouveau_statut === 'validé' && $ancien_statut !== 'validé') {
                addToHistorique($pdo, $voeu_info, $commentaire);
                $message_suffix = " et affectation créée";
            } else {
                $message_suffix = "";
            }
            
            // Mettre à jour le statut dans voeux_professeurs
            $stmt = $pdo->prepare("UPDATE voeux_professeurs SET statut = ?, commentaire_chef = ? WHERE id = ?");
            $stmt->execute([$nouveau_statut, $commentaire, $id_voeu]);
            
            // Journaliser la décision
            enregistrerDecision($pdo, 'voeux_professeurs', $id_voeu, $id_utilisateur_decision, $ancien_statut, $nouveau_statut, $commentaire);
            
            $_SESSION['message'] = "Statut modifié à '" . $nouveau_statut . "'" . $message_suffix;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Définir la constante INCLUDED pour les fichiers inclus
define('INCLUDED', true);

// Inclure le header (avec la barre de navigation supérieure)
include 'header.php';

// Nous ne incluons plus sidebar.php car il cause un double menu
// Le menu est déjà inclus dans header.php
?>

<div class="main-container">
    <div class="content">
        <!-- Contenu de la page ici -->
        <style>
        :root {
                    --primary-bg: #1B2438;
                    --secondary-bg: #1F2B47;
                    --accent-color: #31B7D1;
                    --text-color: #FFFFFF;
                    --text-muted: #7086AB;
                    --border-color: #2A3854;
                    --success-color: #31B7D1; /* Bleu turquoise */
                    --warning-color: #FFA726; /* Orange */
                    --danger-color: #EF5350; /* Rouge */
                    --info-color: #5C6BC0; /* Bleu indigo */
                }
                
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    background-color: var(--primary-bg);
                    color: var(--text-color);
                    display: flex;
                }
                  /* Content wrapper */
                  .content-wrapper {
                    padding: 20px 30px;
                }
                
                /* Styles spécifiques à la validation des vœux */
                .voeux-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                
                .voeu-card {
                    background-color: var(--secondary-bg);
                    border-radius: 8px;
                    padding: 20px;
                    border: 1px solid var(--border-color);
                    transition: all 0.3s;
                }
                
                .voeu-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
                }
                
                .voeu-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid var(--border-color);
                }
                
                .voeu-professor {
                    font-weight: bold;
                    color: var(--accent-color);
                }
                
                .voeu-status {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                
                .status-en_attente {
                    background-color: rgba(255, 167, 38, 0.2);
                    color: var(--warning-color);
                }
                
                .status-validé {
                    background-color: rgba(49, 183, 209, 0.2);
                    color: var(--accent-color);
                }
                
                .status-rejeté {
                    background-color: rgba(239, 83, 80, 0.2);
                    color: var(--danger-color);
                }
                
                .voeu-details {
                    margin-bottom: 15px;
                }
                
                .voeu-detail {
                    display: flex;
                    margin-bottom: 8px;
                }
                
                .voeu-label {
                    font-weight: bold;
                    color: var(--text-muted);
                    min-width: 100px;
                }
                
                .voeu-value {
                    flex: 1;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: pre-wrap; /* Permet le retour à la ligne */
                    word-break: break-word; /* Force le retour à la ligne si nécessaire */
                }
                
                .voeu-hours {
                    display: flex;
                    gap: 10px;
                    margin: 15px 0;
                }
                
                .hour-badge {
                    background-color: rgba(49, 183, 209, 0.1);
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    color: var(--accent-color);
                    border: 1px solid var(--border-color);
                }
                
                .voeu-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .btn {
                    padding: 8px 15px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: bold;
                    transition: all 0.3s;
                    flex: 1;
                    text-align: center;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    border: 1px solid transparent;
                }
                
                /* Nouveaux styles de boutons inspirés de l'image */
                .btn-primary {
                    background-color: rgba(49, 183, 209, 0.1);
                    color: var(--accent-color);
                    border-color: var(--accent-color);
                }
                
                .btn-primary:hover {
                    background-color: rgba(49, 183, 209, 0.2);
                    transform: translateY(-2px);
                }
                
                .btn-success {
                    background-color: rgba(49, 183, 209, 0.1);
                    color: var(--accent-color);
                    border-color: var(--accent-color);
                }
                
                .btn-success:hover {
                    background-color: rgba(49, 183, 209, 0.2);
                    transform: translateY(-2px);
                }
                
                .btn-danger {
                    background-color: rgba(239, 83, 80, 0.1);
                    color: var(--danger-color);
                    border-color: var(--danger-color);
                }
                
                .btn-danger:hover {
                    background-color: rgba(239, 83, 80, 0.2);
                    transform: translateY(-2px);
                }
                
                .btn-warning {
                    background-color: rgba(255, 167, 38, 0.1);
                    color: var(--warning-color);
                    border-color: var(--warning-color);
                }
                
                .btn-warning:hover {
                    background-color: rgba(255, 167, 38, 0.2);
                    transform: translateY(-2px);
                }
                
                .btn-confirm {
                    background-color: rgba(49, 183, 209, 0.1);
                    color: var(--accent-color);
                    border-color: var(--accent-color);
                }
                
                .btn-confirm:hover {
                    border-color: #2aa3b9;
                    transform: translateY(-2px);
                }
                
                .btn-reject {
                    background-color: rgba(49, 183, 209, 0.1);
                    color: var(--danger-color);
                    border-color: var(--danger-color);
                }
                
                .btn-reject:hover {
                    border-color: #e53935;
                    transform: translateY(-2px);
                }
                
                .btn-modify {
                    background-color: rgba(49, 183, 209, 0.1);
                    color: var(--warning-color);
                    border-color: var(--warning-color);
                }
                
                .btn-modify:hover {
                    border-color: #fb8c00;
                    transform: translateY(-2px);
                }
                
                .btn-outline {
                    background-color: transparent;
                    border: 1px solid var(--border-color);
                    color: var(--text-color);
                }
                
                .btn-outline:hover {
                    background-color: rgba(255, 255, 255, 0.05);
                    border-color: var(--accent-color);
                    transform: translateY(-2px);
                }
                
                .search-wrapper {
                    background-color: var(--secondary-bg);
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    border: 1px solid var(--border-color);
                }
                
                .search-wrapper input {
                    width: 100%;
                    padding: 10px;
                    background-color: var(--primary-bg);
                    border: 1px solid var(--border-color);
                    border-radius: 4px;
                    color: var(--text-color);
                }
                
                .search-wrapper input:focus {
                    outline: none;
                    border-color: var(--accent-color);
                }
                
                .filter-section {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 20px;
                }
                #statusFilter option{
                    background-color: var(--secondary-bg);
                    color: var(--accent-color);
                }
                .filter-section select {
                    padding: 10px;
                    background-color: rgba(49, 183, 209, 0.1);
                    border: 1px solid var(--accent-color);
                    border-radius: 4px;
                    color: var(--accent-color);
                    min-width: 200px;
                }
                
                .filter-section select:focus {
                    outline: none;
                    border-color: var(--accent-color);
                }
                
                /* Modal styles */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                }
                
                .modal-content {
                    background-color: var(--secondary-bg);
                    margin: 10% auto;
                    padding: 25px;
                    border-radius: 8px;
                    width: 50%;
                    max-width: 500px;
                    position: relative;
                    border: 1px solid var(--border-color);
                }
                
                .close-modal {
                    position: absolute;
                    right: 20px;
                    top: 15px;
                    font-size: 24px;
                    cursor: pointer;
                    color: var(--text-muted);
                }
                
                .close-modal:hover {
                    color: var(--accent-color);
                }
                
                .modal-title {
                    margin-bottom: 20px;
                    color: var(--accent-color);
                }
                
                .modal-body {
                    margin-bottom: 20px;
                }
                
                .modal-footer {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
                
                textarea {
                    width: 100%;
                    padding: 10px;
                    background-color: var(--primary-bg);
                    border: 1px solid var(--border-color);
                    border-radius: 4px;
                    color: var(--text-color);
                    resize: vertical;
                    min-height: 100px;
                    margin-bottom: 15px;
                }
                
                textarea:focus {
                    outline: none;
                    border-color: var(--accent-color);
                }
                
                .alert {
                    padding: 15px;
                    margin-bottom: 20px;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    border-left: 4px solid transparent;
                    transition: opacity 1s ease-out;
                }
                
                .alert-success {
                    background-color: rgba(49, 183, 209, 0.2);
                    color: var(--accent-color);
                    border-left-color: var(--accent-color);
                }
                
                .alert-danger {
                    background-color: rgba(239, 83, 80, 0.2);
                    color: var(--danger-color);
                    border-left-color: var(--danger-color);
                }
                
                .alert i {
                    font-size: 20px;
                }
                
                .no-voeux {
                    text-align: center;
                    padding: 30px;
                    color: var(--text-muted);
                    grid-column: 1 / -1;
                    border: 1px dashed var(--border-color);
                    border-radius: 8px;
                }
                #new_status option {
              background-color: var(--secondary-bg);
              color: var(--accent-color);
              padding: 8px;
          }
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .voeux-container {
                        grid-template-columns: 1fr;
                    }
                    
                    .modal-content {
                        width: 90%;
                    }
                }
                




        </style>

        <!-- Contenu spécifique à la page de validation des souhaits -->
        <div class="content-wrapper">
            <h2><i class="fas fa-clipboard-check"></i> Validation des Vœux d'Enseignement</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'alert-danger' : 'alert-success' ?>" id="alertMessage">
                    <i class="fas <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="search-wrapper">
                <input type="text" id="searchInput" placeholder="Rechercher un enseignant, une UE ou une filière..." aria-label="Rechercher">
                <div id="search-info" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);"></div>
            </div>
            
            <div class="filter-section">
                <select id="statusFilter" aria-label="Filtrer par statut">
                    <option value="tous">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="validé">Validé</option>
                    <option value="rejeté">Rejeté</option>
                </select>
                
                <div style="flex: 1; text-align: right;">
                    <div class="voeux-count" style="padding: 0.5rem; color: var(--text-muted); font-size: 0.9rem;">
                        <?php 
                        $count_en_attente = 0;
                        $count_valide = 0;
                        $count_rejete = 0;
                        
                        foreach ($voeux as $v) {
                            if ($v['statut'] === 'en_attente') $count_en_attente++;
                            else if ($v['statut'] === 'validé') $count_valide++;
                            else if ($v['statut'] === 'rejeté') $count_rejete++;
                        }
                        
                        $total = count($voeux);
                        ?>
                        <span class="badge status-en_attente" style="margin-right: 0.5rem;"><i class="fas fa-clock"></i> En attente: <?= $count_en_attente ?></span>
                        <span class="badge status-validé" style="margin-right: 0.5rem;"><i class="fas fa-check-circle"></i> Validés: <?= $count_valide ?></span>
                        <span class="badge status-rejeté"><i class="fas fa-times-circle"></i> Rejetés: <?= $count_rejete ?></span>
                    </div>
                </div>
            </div>
            
            <div class="voeux-container" id="voeux-container">
                <?php if (!empty($voeux)): ?>
                    <?php foreach ($voeux as $voeu): ?>
                        <?php 
                        $volume_field = "volume_horaire_" . strtolower($voeu['type_ue']);
                        $total_hours = $voeu[$volume_field] ?? 0;
                        ?>
                        <div class="voeu-card" data-status="<?= $voeu['statut'] ?? 'en_attente' ?>">
                            <div class="voeu-header">
                                <div class="voeu-professor">
                                    <i class="fas fa-user-circle"></i>
                                    <?= htmlspecialchars($voeu['nom'] . ' ' . $voeu['prenom']) ?>
                                </div>
                                <div class="voeu-status status-<?= $voeu['statut'] ?? 'en_attente' ?>">
                                    <i class="fas <?= $voeu['statut'] === 'validé' ? 'fa-check-circle' : ($voeu['statut'] === 'rejeté' ? 'fa-times-circle' : 'fa-clock') ?>"></i>
                                    <?= ucfirst($voeu['statut'] ?? 'En attente') ?>
                                </div>
                            </div>
                            
                            <div class="voeu-details">
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-book"></i> UE:</span>
                                    <span class="voeu-value"><?= htmlspecialchars($voeu['code'] . ' - ' . $voeu['intitule']) ?></span>
                                </div>
                                
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-graduation-cap"></i> Filière:</span>
                                    <span class="voeu-value"><?= htmlspecialchars($voeu['nom_filiere']) ?></span>
                                </div>
                                
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-sort-numeric-up"></i> Priorité:</span>
                                    <span class="voeu-value"><?= $voeu['priorite'] ?></span>
                                </div>
                                
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-tag"></i> Type:</span>
                                    <span class="voeu-value"><?= $voeu['type_ue'] ?></span>
                                </div>
                                
                                <?php if (!empty($voeu['commentaire'])): ?>
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-comment"></i> Commentaire:</span>
                                    <span class="voeu-value"><?= nl2br(htmlspecialchars($voeu['commentaire'])) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($voeu['commentaire_chef'])): ?>
                                <div class="voeu-detail">
                                    <span class="voeu-label"><i class="fas fa-comment-dots"></i> Votre avis:</span>
                                    <span class="voeu-value"><?= nl2br(htmlspecialchars($voeu['commentaire_chef'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="voeu-hours">
                                <span class="hour-badge">
                                    <i class="fas fa-clock"></i> <?= $voeu['type_ue'] ?>: <?= $total_hours ?>h
                                </span>
                                
                                <?php 
                                // Ajouter des indicateurs supplémentaires
                                $semester = $voeu['semestre'];
                                $year = $voeu['annee_universitaire'];
                                ?>
                                <span class="hour-badge" style="background-color: rgba(92, 107, 192, 0.15); color: var(--info-color); border-color: var(--info-color);">
                                    <i class="fas fa-calendar-alt"></i> S<?= $semester ?> - <?= $year ?>
                                </span>
                            </div>
                            
                            <div class="voeu-actions">
                                <?php if ($voeu['statut'] === 'en_attente'): ?>
                                    <button class="btn btn-success" onclick="showValidationModal(<?= $voeu['id'] ?>)">
                                        <i class="fas fa-check"></i> Valider
                                    </button>
                                    <button class="btn btn-danger" onclick="showRejectionModal(<?= $voeu['id'] ?>)">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-warning" onclick="showModificationModal(<?= $voeu['id'] ?>, '<?= $voeu['statut'] ?>')">
                                        <i class="fas fa-edit"></i> Modifier le statut
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-voeux">
                        <i class="fas fa-inbox"></i>
                        <p>Aucun vœu à valider pour le moment</p>
                        <span style="font-size: 0.9rem; color: var(--text-muted); max-width: 500px; text-align: center;">
                            Les enseignants n'ont pas encore soumis de vœux pour les UE disponibles. Revenez plus tard.
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal de validation -->
        <div id="validationModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="hideValidationModal()">&times;</span>
                <h3 class="modal-title"><i class="fas fa-check-circle"></i> Valider le vœu</h3>
                <form id="validationForm" method="post">
                    <input type="hidden" name="action" value="valider">
                    <input type="hidden" name="id_voeu" id="validation_id_voeu">
                    <div class="modal-body">
                        <p style="margin-bottom: 1rem; color: var(--text-muted);">
                            La validation de ce vœu créera une affectation pour l'enseignant et enregistrera cette décision dans l'historique.
                        </p>
                        <label for="validation_commentaire">Commentaire (optionnel):</label>
                        <textarea name="commentaire" id="validation_commentaire" placeholder="Ajoutez un commentaire si nécessaire"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="hideValidationModal()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-confirm">
                            <i class="fas fa-check"></i> Valider
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de rejet -->
        <div id="rejectionModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="hideRejectionModal()">&times;</span>
                <h3 class="modal-title"><i class="fas fa-times-circle"></i> Rejeter le vœu</h3>
                <form id="rejectionForm" method="post">
                    <input type="hidden" name="action" value="rejeter">
                    <input type="hidden" name="id_voeu" id="rejection_id_voeu">
                    <div class="modal-body">
                        <p style="margin-bottom: 1rem; color: var(--text-muted);">
                            Le rejet de ce vœu notifiera l'enseignant que sa demande n'a pas été approuvée. Merci d'expliquer la raison.
                        </p>
                        <label for="rejection_commentaire">Commentaire (requis):</label>
                        <textarea name="commentaire" id="rejection_commentaire" required placeholder="Veuillez indiquer la raison du rejet"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="hideRejectionModal()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-reject">
                            <i class="fas fa-check"></i> Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de modification -->
        <div id="modificationModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="hideModificationModal()">&times;</span>
                <h3 class="modal-title"><i class="fas fa-edit"></i> Modifier le statut</h3>
                <form id="modificationForm" method="post">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id_voeu" id="modification_id_voeu">
                    <div class="modal-body">
                        <p style="margin-bottom: 1rem; color: var(--text-muted);">
                            Modifier le statut d'un vœu mettra à jour son état et peut affecter les affectations existantes.
                        </p>
                        <label for="new_status">Nouveau statut:</label>
                        <select name="new_status" id="new_status" required>
                            <option value="en_attente">En attente</option>
                            <option value="validé">Validé</option>
                            <option value="rejeté">Rejeté</option>
                        </select>
                        
                        <label for="modification_commentaire">Commentaire (optionnel):</label>
                        <textarea name="commentaire" id="modification_commentaire" placeholder="Expliquez la raison de ce changement de statut"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="hideModificationModal()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-modify">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Fonctions pour les modals
            function showValidationModal(id_voeu) {
                document.getElementById('validation_id_voeu').value = id_voeu;
                document.getElementById('validationModal').style.display = 'block';
                document.getElementById('validation_commentaire').focus();
                
                // Empêcher le défilement de la page en arrière-plan
                document.body.style.overflow = 'hidden';
            }
            
            function hideValidationModal() {
                document.getElementById('validationModal').style.display = 'none';
                document.getElementById('validation_commentaire').value = '';
                
                // Réactiver le défilement
                document.body.style.overflow = 'auto';
            }
            
            function showRejectionModal(id_voeu) {
                document.getElementById('rejection_id_voeu').value = id_voeu;
                document.getElementById('rejectionModal').style.display = 'block';
                document.getElementById('rejection_commentaire').focus();
                
                // Empêcher le défilement de la page en arrière-plan
                document.body.style.overflow = 'hidden';
            }
            
            function hideRejectionModal() {
                document.getElementById('rejectionModal').style.display = 'none';
                document.getElementById('rejection_commentaire').value = '';
                
                // Réactiver le défilement
                document.body.style.overflow = 'auto';
            }
            
            function showModificationModal(id_voeu, current_status) {
                document.getElementById('modification_id_voeu').value = id_voeu;
                document.getElementById('new_status').value = current_status;
                document.getElementById('modificationModal').style.display = 'block';
                
                // Empêcher le défilement de la page en arrière-plan
                document.body.style.overflow = 'hidden';
            }
            
            function hideModificationModal() {
                document.getElementById('modificationModal').style.display = 'none';
                document.getElementById('modification_commentaire').value = '';
                
                // Réactiver le défilement
                document.body.style.overflow = 'auto';
            }
            
            // Fermer la modal si on clique en dehors
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    hideValidationModal();
                    hideRejectionModal();
                    hideModificationModal();
                }
            };
            
            // Filtrage des vœux et recherche améliorée
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const voeuxContainer = document.getElementById('voeux-container');
                const voeuxCards = document.querySelectorAll('.voeu-card');
                const searchInfo = document.getElementById('search-info');
                
                function filterVoeux() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    const statusValue = statusFilter.value;
                    
                    let visibleCount = 0;
                    let totalCount = voeuxCards.length;
                    
                    voeuxCards.forEach(card => {
                        const cardText = card.textContent.toLowerCase();
                        const cardStatus = card.getAttribute('data-status');
                        
                        const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                        const matchesStatus = statusValue === 'tous' || cardStatus === statusValue;
                        
                        const isVisible = matchesSearch && matchesStatus;
                        card.style.display = isVisible ? 'block' : 'none';
                        
                        if (isVisible) {
                            visibleCount++;
                            
                            // Mettre en évidence les résultats de recherche si le terme n'est pas vide
                            if (searchTerm !== '') {
                                highlightSearchResults(card, searchTerm);
                            } else {
                                // Supprimer les surlignages s'il n'y a pas de terme de recherche
                                removeHighlights(card);
                            }
                        }
                    });
                    
                    // Mettre à jour les informations de recherche
                    updateSearchInfo(visibleCount, totalCount, searchTerm, statusValue);
                    
                    // Afficher un message s'il n'y a pas de résultats
                    const noResults = document.querySelector('.no-results');
                    if (visibleCount === 0 && !noResults) {
                        const noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-voeux no-results';
                        noResultsMsg.innerHTML = `
                            <i class="fas fa-search"></i>
                            <p>Aucun résultat ne correspond à votre recherche</p>
                            <span style="font-size: 0.9rem; color: var(--text-muted); text-align: center;">
                                Essayez de modifier vos critères de recherche ou de filtre
                            </span>
                        `;
                        voeuxContainer.appendChild(noResultsMsg);
                    } else if (visibleCount > 0 && noResults) {
                        noResults.remove();
                    }
                }
                
                function updateSearchInfo(visible, total, searchTerm, statusValue) {
                    if (searchTerm !== '' || statusValue !== 'tous') {
                        let message = `${visible} sur ${total} vœux affichés`;
                        if (searchTerm !== '') {
                            message += ` pour "${searchTerm}"`;
                        }
                        if (statusValue !== 'tous') {
                            message += ` avec le statut "${statusValue.replace('_', ' ')}"`;
                        }
                        searchInfo.textContent = message;
                        searchInfo.style.display = 'block';
                    } else {
                        searchInfo.style.display = 'none';
                    }
                }
                
                function highlightSearchResults(card, term) {
                    // Supprimer d'abord les surlignages précédents
                    removeHighlights(card);
                    
                    // Fonction pour mettre en évidence les correspondances dans un nœud de texte
                    function highlightInTextNode(textNode) {
                        const parent = textNode.parentNode;
                        if (parent.nodeName === 'MARK' || parent.classList.contains('voeu-status')) {
                            return; // Éviter de surligner dans des noeuds déjà surlignés ou dans les statuts
                        }
                        
                        const text = textNode.nodeValue;
                        const termIndex = text.toLowerCase().indexOf(term);
                        
                        if (termIndex >= 0) {
                            // Diviser le texte en parties avant, correspondante et après
                            const before = text.substring(0, termIndex);
                            const match = text.substring(termIndex, termIndex + term.length);
                            const after = text.substring(termIndex + term.length);
                            
                            // Créer des nœuds pour chaque partie
                            const beforeNode = document.createTextNode(before);
                            const matchNode = document.createElement('mark');
                            matchNode.style.backgroundColor = 'rgba(49, 183, 209, 0.3)';
                            matchNode.style.color = 'var(--text-color)';
                            matchNode.style.padding = '0 2px';
                            matchNode.style.borderRadius = '2px';
                            matchNode.appendChild(document.createTextNode(match));
                            const afterNode = document.createTextNode(after);
                            
                            // Remplacer le nœud de texte original par les trois nouveaux nœuds
                            parent.insertBefore(beforeNode, textNode);
                            parent.insertBefore(matchNode, textNode);
                            parent.insertBefore(afterNode, textNode);
                            parent.removeChild(textNode);
                            
                            // Continuer à rechercher dans le texte après
                            highlightInTextNode(afterNode);
                        }
                    }
                    
                    // Parcourir tous les nœuds de texte dans la carte
                    const walker = document.createTreeWalker(card, NodeFilter.SHOW_TEXT, null, false);
                    let node;
                    const textNodes = [];
                    
                    while (node = walker.nextNode()) {
                        textNodes.push(node);
                    }
                    
                    // Traiter chaque nœud de texte
                    textNodes.forEach(textNode => highlightInTextNode(textNode));
                }
                
                function removeHighlights(element) {
                    const marks = element.querySelectorAll('mark');
                    marks.forEach(mark => {
                        const parent = mark.parentNode;
                        const textNode = document.createTextNode(mark.textContent);
                        parent.replaceChild(textNode, mark);
                        parent.normalize(); // Fusionner les nœuds de texte adjacents
                    });
                }
                
                // Attacher les écouteurs d'événements
                searchInput.addEventListener('input', filterVoeux);
                statusFilter.addEventListener('change', filterVoeux);
                
                // Disparition automatique des messages d'alerte après 5 secondes
                const alertMessage = document.getElementById('alertMessage');
                if (alertMessage) {
                    setTimeout(() => {
                        alertMessage.style.opacity = '0';
                        alertMessage.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            alertMessage.style.display = 'none';
                        }, 500);
                    }, 5000);
                }
                
                // Initialiser le filtrage au chargement
                filterVoeux();
                
                // Support des touches Echap pour fermer les modals
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hideValidationModal();
                        hideRejectionModal();
                        hideModificationModal();
                    }
                });
                
                // Ajouter des écouteurs aux formulaires pour éviter les soumissions accidentelles
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        // Désactiver les boutons après soumission pour éviter les doubles soumissions
                        const buttons = form.querySelectorAll('button[type="submit"]');
                        buttons.forEach(button => {
                            button.disabled = true;
                            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                        });
                    });
                });
            });
        </script>
    </div>
</div>

<?php
// Inclure le footer
include 'footer_chef.php';
?>