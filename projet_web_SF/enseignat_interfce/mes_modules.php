<?php
session_start();
include_once 'db.php';

// Vérification des permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../../login.php');
    exit();
}

$pdo = connectDB();
$id_enseignant = $_SESSION['user_id'];

// Récupérer l'année universitaire actuelle (septembre à août)
$currentYear = date('Y');
$currentMonth = date('n'); // 'n' donne le mois sans zéro initial (1 pour janvier)

if ($currentMonth >= 9) {
    // De septembre à décembre
    $annee_actuelle = $currentYear . '-' . ($currentYear + 1);
} else {
    // De janvier à août
    $annee_actuelle = ($currentYear - 1) . '-' . $currentYear;
}

// Récupérer les affectations de l'enseignant depuis historique_affectations
$stmt = $pdo->prepare("
    SELECT 
        ha.id,
        ue.code,
        ue.intitule,
        ue.semestre,
        ue.specialite,
        ha.type_cours,
        CASE 
            WHEN ha.type_cours = 'CM' THEN ue.volume_horaire_cm
            WHEN ha.type_cours = 'TD' THEN ue.volume_horaire_td
            WHEN ha.type_cours = 'TP' THEN ue.volume_horaire_tp
        END as volume_horaire,
        f.nom as nom_filiere,
        d.nom as nom_departement,
        ha.statut,
        ha.annee_universitaire,
        ha.date_affectation
    FROM historique_affectations ha
    JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
    LEFT JOIN filieres f ON ue.id_filiere = f.id
    LEFT JOIN departements d ON ue.id_departement = d.id
    WHERE ha.id_utilisateur = ?
      AND ha.role = 'enseignant'
      AND ha.annee_universitaire = ?
    ORDER BY ue.semestre, ue.code
");
$stmt->execute([$id_enseignant, $annee_actuelle]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer la charge horaire totale
$charge_totale = 0;
$charge_cm = 0;
$charge_td = 0;
$charge_tp = 0;

foreach ($modules as $module) {
    switch ($module['type_cours']) {
        case 'CM':
            $charge_cm += $module['volume_horaire'];
            $charge_totale += $module['volume_horaire'];
            break;
        case 'TD':
            $charge_td += $module['volume_horaire'];
            $charge_totale += $module['volume_horaire'];
            break;
        case 'TP':
            $charge_tp += $module['volume_horaire'];
            $charge_totale += $module['volume_horaire'];
            break;
    }
}

$charge_minimale = 192;
$charge_suffisante = $charge_totale >= $charge_minimale;

// Inclure le header
include_once 'header_enseignant.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Modules Affectés - Enseignant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css"> <!-- Assurez-vous que ce fichier existe et contient les styles généraux -->
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
            --card-hover-transform: translateY(-3px);
            --card-border-radius: 0.75rem;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content {
            flex-grow: 1;
            padding: 1.5rem;
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

        .stats-card {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--card-border-radius);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: var(--card-hover-transform);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            border-color: var(--accent-color);
        }

        .stats-card .card-body {
            padding: 1.25rem;
        }

        .stats-card h6 {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stats-card h3 {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0;
        }

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

        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #FFC107;
            color: #FFC107;
        }

        .table {
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-bg);
            color: var(--text-muted);
            font-weight: 500;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(49, 183, 209, 0.05);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
        }

        .badge {
            padding: 0.35em 0.65em;
            border-radius: 0.375rem;
            font-size: 0.875em;
            font-weight: 500;
        }

        .badge.bg-primary { background-color: rgba(49, 183, 209, 0.2) !important; color: var(--accent-color) !important; }
        .badge.bg-success { background-color: rgba(52, 199, 89, 0.2) !important; color: #34C759 !important; }
        .badge.bg-warning { background-color: rgba(255, 159, 10, 0.2) !important; color: #FF9F0A !important; }
        .badge.bg-info { background-color: rgba(90, 200, 250, 0.2) !important; color: #5AC8FA !important; }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .content {
                padding: 1.25rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 1rem;
            }
            
            .stats-card h3 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
 
        <!-- Assurez-vous que le header inclus gère la sidebar si nécessaire -->
        <div class="content">
            <div class="container-fluid">
                <h4 class="mb-4">Mes Modules Affectés</h4>

                <!-- En-tête avec la charge horaire -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-clock"></i> Ma charge horaire
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="card-body">
                                        <h6 class="card-title">Charge totale</h6>
                                        <h3 class="mb-0"><?php echo $charge_totale; ?>h</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="card-body">
                                        <h6 class="card-title">Cours Magistraux</h6>
                                        <h3 class="mb-0"><?php echo $charge_cm; ?>h</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="card-body">
                                        <h6 class="card-title">Travaux Dirigés</h6>
                                        <h3 class="mb-0"><?php echo $charge_td; ?>h</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="card-body">
                                        <h6 class="card-title">Travaux Pratiques</h6>
                                        <h3 class="mb-0"><?php echo $charge_tp; ?>h</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$charge_suffisante): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Attention : Votre charge horaire est inférieure au minimum requis (<?php echo $charge_minimale; ?>h).
                            Il vous manque <?php echo $charge_minimale - $charge_totale; ?>h.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Liste des modules -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book"></i> Mes modules affectés
                    </div>
                    <div class="card-body">
                        <?php if (empty($modules)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Vous n'avez pas encore de modules affectés pour cette année universitaire.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Code UE</th>
                                            <th>Intitulé</th>
                                            <th>Type</th>
                                            <th>Volume horaire</th>
                                            <th>Filière</th>
                                            <th>Département</th>
                                            <th>Semestre</th>
                                            <th>Statut</th>
                                            <th>Date d'affectation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modules as $module): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($module['code']); ?></td>
                                                <td><?php echo htmlspecialchars($module['intitule']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $module['type_cours'] === 'CM' ? 'primary' : 
                                                            ($module['type_cours'] === 'TD' ? 'success' : 'warning'); 
                                                    ?>">
                                                        <?php echo $module['type_cours']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $module['volume_horaire']; ?>h</td>
                                                <td><?php echo htmlspecialchars($module['nom_filiere']); ?></td>
                                                <td><?php echo htmlspecialchars($module['nom_departement']); ?></td>
                                                <td>S<?php echo $module['semestre']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $module['statut'] === 'validé' ? 'success' : 
                                                            ($module['statut'] === 'affecté' ? 'info' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($module['statut']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($module['date_affectation'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
   

<?php include_once 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fonction pour vérifier la charge horaire
function checkChargeHoraire() {
    fetch('check_charge_horaire.php')
        .then(response => response.json())
        .then(data => {
            if (!data.charge_suffisante) {
                // Mettre à jour l'alerte si elle existe déjà
                let alertDiv = document.querySelector('.alert-warning');
                if (!alertDiv) {
                    alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-warning mt-3';
                    document.querySelector('.card-body').appendChild(alertDiv);
                }
                
                alertDiv.innerHTML = `
                    <i class=\"fas fa-exclamation-triangle me-2\"></i>
                    Attention : Votre charge horaire est inférieure au minimum requis (${data.charge_minimale}h).
                    Il vous manque ${data.heures_manquantes}h.
                `;
            } else {
                // Supprimer l'alerte si elle existe
                const alertDiv = document.querySelector('.alert-warning');
                if (alertDiv) {
                    alertDiv.remove();
                }
            }
        })
        .catch(error => console.error('Erreur:', error));
}

// Vérifier la charge horaire toutes les 5 minutes
setInterval(checkChargeHoraire, 300000);

// Vérifier immédiatement au chargement de la page
document.addEventListener('DOMContentLoaded', checkChargeHoraire);
</script>

</body>
</html> 