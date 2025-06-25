<?php
// Définir le chemin de base de l'application
define('BASE_PATH', '/projet_web/web-project2');

// Titre de la page
$page_title = "Mon Profil";

// Inclure le header (qui initialise la session)
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . "/gestion-module-ue/includes/header.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: /projet_web/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM utilisateurs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Vérifier si l'utilisateur existe
if (!$user) {
    $_SESSION['error'] = "Utilisateur non trouvé ou vous n'avez pas les droits nécessaires.";
    header('Location: /projet_web/login.php');
    exit;
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toujours récupérer les infos actuelles de l'utilisateur
    $nom = $user['nom'];
    $prenom = $user['prenom'];
    $email = $user['email'];
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'];
    $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'];

    $error = false;
    $success = false;

    // Si un nouveau mot de passe est fourni
    if (!empty($nouveau_mot_de_passe)) {
        if ($nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $error = true;
        } elseif (strlen($nouveau_mot_de_passe) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $error = true;
        }
    }

    if (!$error && !empty($nouveau_mot_de_passe)) {
        // Mettre à jour le mot de passe uniquement
                $hashed_password = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                $query = "UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Mot de passe mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du mot de passe.";
        }
        // Recharger les infos utilisateur
            $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        $nom = $user['nom'];
        $prenom = $user['prenom'];
        $email = $user['email'];
    }
}

// Sécurisation des champs pour l'affichage
$nom = isset($user['nom']) ? $user['nom'] : '';
$prenom = isset($user['prenom']) ? $user['prenom'] : '';
$email = isset($user['email']) ? $user['email'] : '';
?>

<style>
    .main-content {
        padding: var(--content-padding);
    }

    .profile-section {
        background-color: var(--secondary-bg);
        border-radius: 0.75rem;
        padding: var(--card-padding);
        margin-bottom: var(--section-margin);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid var(--border-color);
    }
    
    .profile-section:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
    }

    .profile-header {
        margin-bottom: var(--spacing-lg);
        display: flex;
        align-items: center;
    }
    
    .profile-avatar {
        width: 50px;
        height: 50px;
        background-color: var(--accent-color);
        color: var(--text-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: var(--spacing-md);
        box-shadow: 0 3px 8px rgba(49, 183, 209, 0.2);
        }
    
    .profile-info h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: var(--spacing-xs);
        color: var(--text-color);
    }
    
    .profile-info p {
        font-size: 0.95rem;
        margin-bottom: var(--spacing-sm);
        color: var(--text-muted);
    }
    
    .section-title {
        font-size: 1.25rem;
        color: var(--text-color);
        font-weight: 600;
        margin-bottom: var(--spacing-lg);
        padding-bottom: var(--spacing-sm);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: var(--spacing-sm);
        font-size: 1.1rem;
        color: var(--accent-color);
    }

    .form-control {
        background-color: var(--primary-bg);
        border: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 0.85rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.25s ease;
    }

    .form-control:focus {
        background-color: var(--secondary-bg);
        border-color: var(--accent-color);
        color: var(--text-color);
        box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
    }
    
    .form-control:hover:not(:focus) {
        border-color: var(--accent-color);
    }

    .form-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: var(--spacing-sm);
        font-weight: 500;
    }

    .btn-action {
        background-color: var(--accent-color);
        color: var(--text-color);
        border: none;
        padding: 0.85rem 1.75rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(49, 183, 209, 0.2);
    }

    .btn-action:hover {
        background-color: #2ca5bd;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(49, 183, 209, 0.3);
    }
    
    .btn-action:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(49, 183, 209, 0.3);
    }

    .alert {
        border-radius: 0.75rem;
        margin-bottom: var(--spacing-lg);
        padding: var(--spacing-md);
        display: flex;
        align-items: flex-start;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .alert i {
        margin-right: var(--spacing-sm);
        font-size: 1.25rem;
    }

    .alert-success {
        background-color: rgba(52, 199, 89, 0.1);
        border-left: 4px solid #34C759;
        color: #34C759;
    }

    .alert-danger {
        background-color: rgba(255, 69, 58, 0.1);
        border-left: 4px solid #FF453A;
        color: #FF453A;
    }

    .text-helper {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-top: var(--spacing-sm);
        margin-bottom: var(--spacing-md);
        display: flex;
        align-items: center;
    }
    
    .text-helper i {
        margin-right: var(--spacing-sm);
        font-size: 0.8rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }
    
    .badge {
        background-color: var(--accent-color);
        color: var(--text-color);
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .profile-info h2 {
            font-size: 1.25rem;
        }
        
        .section-title {
            font-size: 1.1rem;
        }
    }
</style>


        <div class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-user-circle me-2"></i>Mon Profil</h4>
                </div>

                    <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $_SESSION['success']; ?></div>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $_SESSION['error']; ?></div>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

            <!-- Profile Information -->
            <div class="profile-section">
                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    Informations Personnelles
                </div>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($nom . ' ' . $prenom); ?></h2>
                        <p><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge">
                            <i class="fas fa-user-tie"></i>
                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                        </span>
                    </div>
                            </div>

                <!-- Update Profile Form -->
                <form method="POST" class="mb-4">
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="nom">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" readonly>
                            </div>
                        <div>
                            <label class="form-label" for="prenom">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" readonly>
                        </div>
                        <div>
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>
                    </div>
                    <div class="text-helper">
                        <i class="fas fa-info-circle"></i>
                        Les informations personnelles ne peuvent pas être modifiées.
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="profile-section">
                <div class="section-title">
                    <i class="fas fa-key"></i>
                    Modification du Mot de Passe
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="nouveau_mot_de_passe" 
                                   name="nouveau_mot_de_passe" minlength="8">
                        </div>
                        <div>
                            <label class="form-label" for="confirmer_mot_de_passe">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirmer_mot_de_passe" 
                                   name="confirmer_mot_de_passe">
                        </div>
                    </div>
                    <div class="text-helper">
                        <i class="fas fa-shield-alt"></i>
                        Pour votre sécurité, utilisez un mot de passe fort avec au moins 8 caractères incluant des lettres, chiffres et symboles.
                    </div>
                    <button type="submit" class="btn-action">
                        <i class="fas fa-key"></i>
                        Mettre à jour le mot de passe
                            </button>
                    </form>
            </div>
        </div>


<script>
    // Add animation when alerts are present
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
            }, 5000); // Hide alerts after 5 seconds
        }
    });
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . "/gestion-module-ue/includes/footer.php"; ?>
