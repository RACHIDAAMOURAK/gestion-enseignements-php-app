<?php

// Démarrer une session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'db.php'; // Si pas déjà inclus
$pdo = connectDB();

// Vérification des permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'enseignant')) {
    echo "Accès refusé.";
    exit();
}

$id_utilisateur = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT nom, prenom, email, specialite, id_departement, 
           date_creation as date_inscription
    FROM utilisateurs 
    WHERE id = ?
");
$stmt->execute([$id_utilisateur]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer le nom du département
$stmt = $pdo->prepare("SELECT nom FROM departements WHERE id = ?");
$stmt->execute([$user_info['id_departement']]);
$departement = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement de la mise à jour du profil
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Récupérer les données du formulaire
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Vérifier si l'utilisateur souhaite changer son mot de passe
    $update_password = false;
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        // Vérifier l'ancien mot de passe
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id_utilisateur]);
        $password_hash = $stmt->fetchColumn();
        
        if (password_verify($current_password, $password_hash)) {
            if ($new_password === $confirm_password) {
                $update_password = true;
            } else {
                $error_message = "Les nouveaux mots de passe ne correspondent pas.";
            }
        } else {
            $error_message = "Le mot de passe actuel est incorrect.";
        }
    }
    
    // Mettre à jour les informations si aucune erreur
    if (empty($error_message)) {
        try {
            $pdo->beginTransaction();
            
            // Mise à jour du mot de passe si demandé
            if ($update_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs
                    SET mot_de_passe = ?
                    WHERE id = ?
                ");
                $stmt->execute([$password_hash, $id_utilisateur]);
                
                $success_message = "Votre mot de passe a été mis à jour avec succès.";
            } else {
                $success_message = "Aucune modification effectuée.";
            }
            
            $pdo->commit();
            
            // Récupérer les infos mises à jour
            $stmt = $pdo->prepare("
                SELECT nom, prenom, email, specialite, id_departement, 
                      date_creation as date_inscription
                FROM utilisateurs 
                WHERE id = ?
            ");
            $stmt->execute([$id_utilisateur]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
        }
    }
}

// Inclure le header avec la barre de navigation
include 'header_enseignant.php';
?>

<style>
    /* Enhanced styles for the profile page */
    :root {
        --card-hover-transform: translateY(-3px);
        --card-border-radius: 0.75rem;
    }
    
    .content h1 {
        color: var(--text-color);
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 1.75rem;
        position: relative;
        padding-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    
    .content h1::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 50px;
        background: var(--accent-color);
        border-radius: 3px;
    }
    
    .content h1 i {
        margin-right: 0.75rem;
        color: var(--accent-color);
    }
    
    /* Animation d'entrée */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card {
        background-color: var(--secondary-bg);
        border-radius: var(--card-border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
        animation: fadeIn 0.3s ease-out;
        margin-bottom: 1.75rem;
    }
    
    .card:hover {
        transform: var(--card-hover-transform);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
        background-color: rgba(49, 183, 209, 0.05);
        border-bottom: 1px solid var(--border-color);
        color: var(--accent-color);
        font-weight: 600;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
    }
    
    .card-header i {
        margin-right: 0.75rem;
        font-size: 1.1rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    /* Form elements */
    .form-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.65rem;
        font-weight: 500;
    }
    
    .form-control {
        background-color: rgba(27, 36, 56, 0.5);
        border: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 0.85rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.25s ease;
    }
    
    .form-control:focus {
        background-color: rgba(27, 36, 56, 0.7);
        border-color: var(--accent-color);
        color: var(--text-color);
        box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
    }
    
    .form-control:hover:not(:focus) {
        border-color: rgba(49, 183, 209, 0.5);
    }
    
    .form-control:disabled {
        opacity: 0.8;
        cursor: not-allowed;
        background-color: rgba(27, 36, 56, 0.3);
    }
    
    /* Alerts */
    .alert {
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: flex-start;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .alert-success {
        background-color: rgba(76, 175, 80, 0.1);
        border-left: 4px solid #4CAF50;
        color: #4CAF50;
    }
    
    .alert-danger {
        background-color: rgba(239, 83, 80, 0.1);
        border-left: 4px solid #EF5350;
        color: #EF5350;
    }
    
    /* Button styles */
    .btn-primary {
        background-color: var(--accent-color);
        color: var(--text-color);
        border: none;
        padding: 0.85rem 1.75rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(49, 183, 209, 0.2);
    }
    
    .btn-primary:hover {
        background-color: #2ca5bd;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(49, 183, 209, 0.3);
    }
    
    .btn-primary:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(49, 183, 209, 0.3);
    }
    
    /* Stats styling */
    .stat-item {
        padding: 1.25rem;
        background-color: rgba(49, 183, 209, 0.1);
        border-radius: 0.5rem;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(49, 183, 209, 0.2);
        transition: all 0.25s ease;
    }
    
    .stat-item:hover {
        background-color: rgba(49, 183, 209, 0.15);
        transform: translateY(-2px);
    }
    
    .stat-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .stat-value {
        color: var(--accent-color);
        font-size: 1.75rem;
        font-weight: 600;
    }
    
    .stat-details {
        margin-top: 1.25rem;
    }
    
    .stat-detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding: 0.75rem 1rem;
        background-color: var(--secondary-bg);
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
        transition: all 0.25s ease;
    }
    
    .stat-detail-item:hover {
        background-color: rgba(27, 36, 56, 0.7);
        border-color: var(--accent-color);
    }
    
    .stat-detail-label {
        color: var(--text-muted);
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .stat-detail-label i {
        margin-right: 0.5rem;
        color: var(--accent-color);
        font-size: 0.9rem;
    }
    
    .stat-detail-value {
        color: var(--text-color);
        font-weight: 600;
    }
    
    /* Quick links styling */
    .list-group-item {
        background-color: transparent;
        border-color: var(--border-color);
        padding: 0.85rem 0.5rem;
        transition: all 0.25s ease;
    }
    
    .list-group-item:hover {
        background-color: rgba(49, 183, 209, 0.05);
    }
    
    .list-group-item a {
        color: var(--text-color);
        text-decoration: none;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        font-weight: 500;
    }
    
    .list-group-item a:hover {
        color: var(--accent-color);
    }
    
    .list-group-item i {
        color: var(--accent-color);
        width: 24px;
        font-size: 1rem;
        text-align: center;
        margin-right: 0.75rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .content h1 {
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
    }
    
    @media (max-width: 768px) {
        .content h1 {
            font-size: 1.25rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 1rem;
        }
    }
    
    hr {
        background-color: var(--border-color);
        opacity: 0.5;
    }
    
    h5 {
        color: var(--accent-color);
        font-weight: 600;
        font-size: 1.15rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
    }
    
    h5 i {
        margin-right: 0.5rem;
    }
</style>


    <div class="container-fluid">
        <div class="content">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Informations Personnelles
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" value="<?= htmlspecialchars($user_info['nom']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" value="<?= htmlspecialchars($user_info['prenom']) ?>" disabled>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user_info['email']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="specialite" class="form-label">Spécialité</label>
                                        <input type="text" class="form-control" id="specialite" value="<?= htmlspecialchars(ucfirst($user_info['specialite'] ?? '')) ?>" disabled>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="departement" class="form-label">Département</label>
                                        <input type="text" class="form-control" id="departement" value="<?= htmlspecialchars($departement['nom'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_inscription" class="form-label">Date d'inscription</label>
                                        <input type="text" class="form-control" id="date_inscription" value="<?= htmlspecialchars(date('d/m/Y', strtotime($user_info['date_inscription']))) ?>" disabled>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Rôle</label>
                                        <input type="text" class="form-control" id="role" value="<?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>" disabled>
                                    </div>
                                </div>
                            
                                <hr class="my-4">
                                <h5><i class="fas fa-key"></i> Changer de mot de passe</h5>
                                
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Changer le mot de passe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </div>
                        <div class="card-body">
                            <?php
                            // Récupérer le nombre d'UEs pour lesquelles l'enseignant a exprimé des souhaits
                            $stmt = $pdo->prepare("
                                SELECT COUNT(DISTINCT id_ue) as nb_ue
                                FROM voeux_professeurs
                                WHERE id_utilisateur = ?
                            ");
                            $stmt->execute([$id_utilisateur]);
                            $nb_ue = $stmt->fetchColumn() ?: 0;
                            
                            // Récupérer les heures d'enseignement par type
                            $stmt = $pdo->prepare("
                                SELECT 
                                    SUM(CASE WHEN type_ue = 'CM' THEN ue.volume_horaire_cm ELSE 0 END) as heures_cm,
                                    SUM(CASE WHEN type_ue = 'TD' THEN ue.volume_horaire_td ELSE 0 END) as heures_td,
                                    SUM(CASE WHEN type_ue = 'TP' THEN ue.volume_horaire_tp ELSE 0 END) as heures_tp
                                FROM voeux_professeurs vp
                                JOIN unites_enseignement ue ON vp.id_ue = ue.id
                                WHERE vp.id_utilisateur = ?
                            ");
                            $stmt->execute([$id_utilisateur]);
                            $heures = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $total_heures = ($heures['heures_cm'] ?: 0) + ($heures['heures_td'] ?: 0) + ($heures['heures_tp'] ?: 0);
                            ?>
                            
                            <div class="stat-item">
                                <div class="stat-label">Nombre d'UEs souhaitées</div>
                                <div class="stat-value"><?= $nb_ue ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-label">Total des heures</div>
                                <div class="stat-value"><?= $total_heures ?> h</div>
                            </div>
                            
                            <div class="stat-details">
                                <div class="stat-detail-item">
                                    <span class="stat-detail-label"><i class="fas fa-bookmark"></i> CM:</span>
                                    <span class="stat-detail-value"><?= $heures['heures_cm'] ?: 0 ?> h</span>
                                </div>
                                <div class="stat-detail-item">
                                    <span class="stat-detail-label"><i class="fas fa-users"></i> TD:</span>
                                    <span class="stat-detail-value"><?= $heures['heures_td'] ?: 0 ?> h</span>
                                </div>
                                <div class="stat-detail-item">
                                    <span class="stat-detail-label"><i class="fas fa-flask"></i> TP:</span>
                                    <span class="stat-detail-value"><?= $heures['heures_tp'] ?: 0 ?> h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-link"></i> Liens Rapides
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <a href="souhaits_enseignants.php">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Expression des souhaits</span>
                                    </a>
                                </li>
                                <li class="list-group-item">
                                    <a href="gestion-notes/index.php">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span>Gestion des notes</span>
                                    </a>
                                </li>
                                <li class="list-group-item">
                                    <a href="mes_modules.php">
                                        <i class="fas fa-cog"></i>
                                        <span>Mes modules</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        }
    });
</script>

<?php
// Inclure le footer
include 'footer.php';
?>