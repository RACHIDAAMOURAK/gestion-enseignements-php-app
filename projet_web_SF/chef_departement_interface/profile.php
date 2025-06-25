<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un chef de département
// Adaptation pour fonctionner avec le système d'authentification
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

if (!$user_id || $user_role !== 'chef_departement') {
    header("Location: ../../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once 'db.php';
$pdo = connectDB();

// Récupérer les informations du chef de département
$stmt = $pdo->prepare("
    SELECT id, nom_utilisateur, prenom, email, role, id_departement, specialite, date_creation 
    FROM utilisateurs 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$chef = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si les données utilisateur ont été récupérées correctement
if (!$chef) {
    // Journal de debug pour comprendre pourquoi l'utilisateur n'est pas trouvé
    error_log("Échec de récupération du profil pour l'utilisateur ID: $user_id");
    
    // Créer un tableau temporaire avec les informations de base
    $chef = [
        'id' => $user_id,
        'nom_utilisateur' => $_SESSION['username'] ?? ($_SESSION['nom_utilisateur'] ?? 'Utilisateur'),
        'prenom' => $_SESSION['prenom'] ?? '',
        'email' => $_SESSION['email'] ?? 'Non disponible',
        'role' => $user_role,
        'id_departement' => $_SESSION['id_departement'] ?? 0,
        'specialite' => $_SESSION['specialite'] ?? '',
        'date_creation' => date('Y-m-d H:i:s')
    ];
    
    // Si nous avons un ID de département mais pas d'informations sur l'utilisateur
    if (!empty($chef['id_departement'])) {
        // Essayons d'insérer cet utilisateur dans la base de données
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO utilisateurs (id, nom_utilisateur, prenom, email, role, id_departement, specialite, date_creation, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
                ON DUPLICATE KEY UPDATE nom_utilisateur = VALUES(nom_utilisateur), prenom = VALUES(prenom), 
                                          email = VALUES(email), role = VALUES(role), 
                                          id_departement = VALUES(id_departement), specialite = VALUES(specialite)
            ");
            $insertStmt->execute([
                $chef['id'],
                $chef['nom_utilisateur'],
                $chef['prenom'],
                $chef['email'],
                $chef['role'],
                $chef['id_departement'],
                $chef['specialite']
            ]);
            
            // Journal de debug - utilisateur créé ou mis à jour
            error_log("Utilisateur ID: $user_id créé ou mis à jour dans la base de données");
        } catch (PDOException $e) {
            // Journal de debug en cas d'erreur
            error_log("Erreur lors de la création/mise à jour de l'utilisateur ID: $user_id - " . $e->getMessage());
        }
    }
}

// Récupérer le nom du département
$departement = ['nom' => 'Non assigné'];
if (!empty($chef['id_departement'])) {
    $stmt = $pdo->prepare("SELECT nom FROM departements WHERE id = ?");
    $stmt->execute([$chef['id_departement']]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept) {
        $departement = $dept;
    } else {
        // Si le département n'existe pas, essayons de le créer avec un nom générique
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO departements (id, nom, description, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nom = VALUES(nom)
            ");
            $departementNom = "Département #" . $chef['id_departement'];
            $insertStmt->execute([
                $chef['id_departement'],
                $departementNom,
                "Département créé automatiquement"
            ]);
            $departement['nom'] = $departementNom;
            
            // Journal de debug - département créé
            error_log("Département ID: " . $chef['id_departement'] . " créé dans la base de données");
        } catch (PDOException $e) {
            // Journal de debug en cas d'erreur
            error_log("Erreur lors de la création du département ID: " . $chef['id_departement'] . " - " . $e->getMessage());
        }
    }
}

// Traitement du changement de mot de passe
$response = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier que les nouveaux mots de passe correspondent
    if ($new_password !== $confirm_password) {
        $response = [
            'success' => false, 
            'message' => 'Les nouveaux mots de passe ne correspondent pas.'
        ];
    } else {
        // Vérifier l'ancien mot de passe
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();
        
        if ($hash && password_verify($current_password, $hash)) {
            // Mettre à jour le mot de passe
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            
            if ($stmt->execute([$new_hash, $user_id])) {
                $response = [
                    'success' => true, 
                    'message' => 'Le mot de passe a été modifié avec succès.'
                ];
            } else {
                $response = [
                    'success' => false, 
                    'message' => 'Une erreur est survenue lors de la modification du mot de passe.'
                ];
            }
        } else {
            $response = [
                'success' => false, 
                'message' => 'Le mot de passe actuel est incorrect.'
            ];
        }
    }
}

// Définir le titre de la page
$page_title = "Mon Profil";

// Inclure le header
include 'header.php';
?>

<style>
    /* Styles spécifiques à la page profil */
    :root {
        --card-hover-transform: translateY(-5px);
    }
    
    /* Animation d'entrée */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .profile-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-top: 1.5rem;
        animation: fadeIn 0.3s ease-out;
    }
    
    @media (max-width: 992px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    
    .profile-card, .password-card {
        background-color: var(--secondary-bg);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        transition: var(--transition);
        grid-column: 1 / -1;
    }
    
    .profile-card:hover, .password-card:hover {
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        transform: var(--card-hover-transform);
    }
    
    .card-header {
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--accent-color);
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .card-header i {
        margin-right: 0.75rem;
    }
    
    .profile-details {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .detail-group {
        margin-bottom: 1rem;
    }
    
    .detail-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
        display: block;
    }
    
    .detail-value {
        background-color: rgba(27, 36, 56, 0.5);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 0.75rem 1rem;
        color: var(--text-color);
        font-size: 1rem;
        width: 100%;
        transition: var(--transition);
    }
    
    .detail-value:hover {
        border-color: var(--accent-color);
        background-color: rgba(49, 183, 209, 0.05);
    }
    
    .password-form {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .form-input {
        display: flex;
        flex-direction: column;
    }
    
    .form-input label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
    }
    
    .form-input input {
        background-color: rgba(27, 36, 56, 0.5);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 0.75rem 1rem;
        color: var(--text-color);
        transition: var(--transition);
    }
    
    .form-input input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
    }
    
    .submit-btn {
        background: linear-gradient(135deg, var(--accent-color) 0%, #2C82BE 100%);
        color: white;
        border: none;
        padding: 0.85rem 1.75rem;
        border-radius: var(--border-radius);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 1rem;
        width: fit-content;
        box-shadow: 0 4px 6px rgba(49, 183, 209, 0.2);
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 10px rgba(49, 183, 209, 0.3);
    }
    
    .submit-btn i {
        margin-right: 0.5rem;
    }
    
    .alerts-container {
        margin-bottom: 1.5rem;
        animation: slideDown 0.3s ease-out;
        grid-column: 1 / -1;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        display: flex;
        align-items: center;
        border-left: 4px solid;
    }
    
    .alert i {
        margin-right: 0.75rem;
        font-size: 1.25rem;
    }
    
    .alert-success {
        background-color: rgba(76, 175, 80, 0.1);
        border-color: var(--success-color);
        color: var(--success-color);
    }
    
    .alert-danger {
        background-color: rgba(239, 83, 80, 0.1);
        border-color: var(--danger-color);
        color: var(--danger-color);
    }
    
    .info-text {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
    }
    
    .info-text i {
        margin-right: 0.5rem;
    }
    
    .stats-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
    }
    
    @media (max-width: 768px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
    }
    
    .stat-card {
        background-color: rgba(27, 36, 56, 0.5);
        border-radius: var(--border-radius);
        padding: 1.25rem;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        transition: var(--transition);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        border-color: var(--accent-color);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.5rem;
    }
    
    .account-icon {
        background-color: rgba(49, 183, 209, 0.15);
        color: var(--accent-color);
    }
    
    .calendar-icon {
        background-color: rgba(92, 107, 192, 0.15);
        color: var(--info-color);
    }
    
    .stat-info {
        flex: 1;
    }
    
    .stat-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-muted);
    }
</style>

<div class="main-container">
    <div class="content">
        <h2><i class="fas fa-user-circle"></i> Mon Profil</h2>

        <!-- Messages d'alerte en cas de soumission du formulaire -->
        <?php if ($response): ?>
        <div class="profile-container">
            <div class="alerts-container">
                <div class="alert <?php echo $response['success'] ? 'alert-success' : 'alert-danger'; ?>">
                    <i class="fas <?php echo $response['success'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $response['message']; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Carte de profil principal -->
        <div class="profile-container">
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-id-card"></i> Informations Personnelles
                </div>
                
                <div class="profile-header">
                    <div class="user-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="profile-info">
                        <h3>
                            <?php 
                            if (isset($chef['prenom']) && !empty($chef['prenom'])) {
                                echo htmlspecialchars($chef['prenom'] . ' ' . $chef['nom_utilisateur']);
                            } else {
                                echo htmlspecialchars($chef['nom_utilisateur'] ?? 'Utilisateur');
                            }
                            ?>
                        </h3>
                        <p><?php echo htmlspecialchars($chef['email'] ?? 'Email non disponible'); ?></p>
                        <span class="badge-role">
                            <i class="fas fa-user-tie"></i>
                            <?php echo ucfirst(htmlspecialchars($chef['role'] ?? 'chef_departement')); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon account-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">Chef de Département</div>
                            <div class="stat-label">Rôle système</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon calendar-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">
                                <?php 
                                $date_creation = isset($chef['date_creation']) && $chef['date_creation'] ? 
                                    date('d/m/Y', strtotime($chef['date_creation'])) : 
                                    date('d/m/Y');
                                echo $date_creation;
                                ?>
                            </div>
                            <div class="stat-label">Date d'inscription</div>
                        </div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-group">
                        <label class="detail-label">Nom d'utilisateur</label>
                        <div class="detail-value"><?php echo htmlspecialchars($chef['nom_utilisateur'] ?? 'Non spécifié'); ?></div>
                    </div>
                    
                    <?php if (isset($chef['prenom']) && !empty($chef['prenom'])): ?>
                    <div class="detail-group">
                        <label class="detail-label">Prénom</label>
                        <div class="detail-value"><?php echo htmlspecialchars($chef['prenom']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-group">
                        <label class="detail-label">Email</label>
                        <div class="detail-value"><?php echo htmlspecialchars($chef['email'] ?? 'Non spécifié'); ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <label class="detail-label">Département</label>
                        <div class="detail-value"><?php echo htmlspecialchars($departement['nom'] ?? 'Non assigné'); ?></div>
                    </div>
                    
                    <?php if (isset($chef['specialite']) && !empty($chef['specialite'])): ?>
                    <div class="detail-group">
                        <label class="detail-label">Spécialité</label>
                        <div class="detail-value"><?php echo htmlspecialchars($chef['specialite']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-group">
                        <label class="detail-label">ID Utilisateur</label>
                        <div class="detail-value"><?php echo htmlspecialchars($chef['id'] ?? $user_id ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Ces informations sont en lecture seule. Pour toute modification, veuillez contacter l'administrateur du système.
                </div>
            </div>
            
            <!-- Carte pour changer le mot de passe -->
            <div class="password-card">
                <div class="card-header">
                    <i class="fas fa-key"></i> Modification du Mot de Passe
                </div>
                
                <form method="POST" class="password-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-input">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-input">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-input">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Mettre à jour le mot de passe
                    </button>
                </form>
                
                <div class="info-text">
                    <i class="fas fa-shield-alt"></i>
                    Pour votre sécurité, choisissez un mot de passe fort avec un minimum de 8 caractères incluant lettres, chiffres et caractères spéciaux.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script spécifique à cette page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des animations aux cartes
    const cards = document.querySelectorAll('.profile-card, .password-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Validation du formulaire de mot de passe
    const passwordForm = document.querySelector('form');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (newPasswordInput.value.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return false;
            }
            
            return true;
        });
    }
    
    // Fermeture automatique des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
    }
});
</script>

<?php
// Inclure le footer
include 'footer_chef.php';
?> 