<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/UserManager.php';
include_once '../classes/SessionManager.php';

// Instancier la base de données et les gestionnaires
$database = new Database();
$db = $database->getConnection();
$userManager = new UserManager($db);
$sessionManager = new SessionManager();

$page_title = "Paramètres du système - Administration";
include_once '../includes/header.php';
?>

<!-- Ne pas ouvrir de balise body car elle est déjà ouverte dans header.php -->
<div class="main-container">
    <div class="container-fluid">
        <div class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Paramètres du système</h4>
            </div>
            
            <!-- Section des paramètres -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h5 class="card-title">Paramètres généraux</h5>
                            <p class="card-text text-muted">Configuration générale du système</p>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="siteName" class="form-label">Nom du site</label>
                                    <input type="text" class="form-control" id="siteName" name="siteName" value="Système de Gestion">
                                </div>
                                <div class="mb-3">
                                    <label for="emailContact" class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" id="emailContact" name="emailContact">
                                </div>
                                <button type="submit" class="btn-action">
                                    <i class="fas fa-save"></i>
                                    Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h5 class="card-title">Notifications</h5>
                            <p class="card-text text-muted">Paramètres des notifications</p>
                            <form method="POST" action="">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="emailNotif" name="emailNotif">
                                    <label class="form-check-label" for="emailNotif">Activer les notifications par email</label>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="systemNotif" name="systemNotif">
                                    <label class="form-check-label" for="systemNotif">Activer les notifications système</label>
                                </div>
                                <button type="submit" class="btn-action">
                                    <i class="fas fa-save"></i>
                                    Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        background-color: var(--secondary-bg) !important;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
    }

    .card-title {
        color: var(--text-color);
        margin-bottom: 0.5rem;
    }

    .form-control {
        background-color: var(--primary-bg);
        border: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 0.75rem;
        border-radius: 0.5rem;
    }

    .form-control:focus {
        background-color: var(--primary-bg);
        border-color: var(--accent-color);
        color: var(--text-color);
        box-shadow: none;
    }

    .form-check-input {
        background-color: var(--primary-bg);
        border-color: var(--border-color);
    }

    .form-check-input:checked {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
    }

    .btn-action {
        background-color: var(--accent-color);
        color: var(--text-color);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>

<?php include_once '../includes/footer.php'; ?>