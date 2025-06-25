<?php
session_start();
include 'db.php';
$pdo = connectDB();

// Vérifier si un ID de rapport est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Erreur: Aucun rapport spécifié";
    header('Location: rapport_details.php');
    exit;
}

$id_rapport = intval($_GET['id']);

// Récupérer les détails du rapport
$stmt = $pdo->prepare("
    SELECT * FROM rapport_charge_departement
    WHERE id = ?
");
$stmt->execute([$id_rapport]);
$rapport = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    $_SESSION['message'] = "Erreur: Rapport introuvable";
    header('Location: generations_rapports2.php');
    exit;
}

// Récupérer les décisions liées à ce rapport
$stmt = $pdo->prepare("
    SELECT 
        jd.*,
        u.nom as nom_decisionnaire, 
        u.prenom as prenom_decisionnaire,
        CASE 
            WHEN jd.type_entite = 'unite_enseignement' THEN ue.code
            WHEN jd.type_entite = 'utilisateur' THEN CONCAT(ut.nom, ' ', ut.prenom)
            WHEN jd.type_entite = 'voeux_professeurs' THEN CONCAT(ue.code, ' - ', f.nom)
            WHEN jd.type_entite = 'affectation' THEN CONCAT(ue.code, ' - ', f.nom)
            WHEN jd.type_entite = 'unite_vacante' THEN CONCAT(ue.code, ' - ', f.nom)
            ELSE jd.id_entite
        END as entite_concernee,
        CASE 
            WHEN jd.type_entite = 'unite_enseignement' THEN ue.intitule
            WHEN jd.type_entite = 'utilisateur' THEN ut.role
            WHEN jd.type_entite IN ('voeux_professeurs', 'affectation', 'unite_vacante') THEN jd.commentaire
            ELSE jd.type_entite
        END as details_entite
    FROM journal_decisions jd
    LEFT JOIN utilisateurs u ON jd.id_utilisateur_decision = u.id
    LEFT JOIN unites_enseignement ue ON 
        (jd.type_entite = 'unite_enseignement' AND jd.id_entite = ue.id) OR
        (jd.type_entite IN ('voeux_professeurs', 'affectation', 'unite_vacante') AND 
         EXISTS (SELECT 1 FROM voeux_professeurs vp WHERE vp.id = jd.id_entite AND vp.id_ue = ue.id))
    LEFT JOIN utilisateurs ut ON jd.type_entite = 'utilisateur' AND jd.id_entite = ut.id
    LEFT JOIN filieres f ON 
        (jd.type_entite IN ('voeux_professeurs', 'affectation', 'unite_vacante') AND 
         EXISTS (SELECT 1 FROM voeux_professeurs vp WHERE vp.id = jd.id_entite AND vp.id_filiere = f.id))
    WHERE jd.id_utilisateur_decision = ? 
    AND jd.date_decision BETWEEN CONCAT(SUBSTRING(?, 1, 4), '-09-01') AND CONCAT(SUBSTRING(?, 6, 4), '-08-31')
    ORDER BY jd.date_decision DESC
");
$stmt->execute([$_SESSION['user_id'], $rapport['annee_universitaire'], $rapport['annee_universitaire']]);
$decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les détails des affectations pour ce rapport
$stmt = $pdo->prepare("
    SELECT 
        ha.*,
        u.nom as nom_enseignant, u.prenom as prenom_enseignant,
        ue.code as code_ue, ue.intitule as intitule_ue,
        f.nom as nom_filiere
    FROM historique_affectations ha
    JOIN utilisateurs u ON ha.id_utilisateur = u.id
    JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
    JOIN filieres f ON ha.id_filiere = f.id
    WHERE ha.id_departement = ? 
    AND ha.annee_universitaire = ? 
    AND ha.semestre = ?
    AND (
        (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) >= 9)  -- Affectations de septembre à décembre
        OR 
        (YEAR(ha.date_affectation) = ? AND MONTH(ha.date_affectation) <= 8)  -- Affectations de janvier à août
    )
    ORDER BY ha.date_affectation DESC
");
// Extraire les années de début et de fin
$annee_debut = substr($rapport['annee_universitaire'], 0, 4);
$annee_fin = substr($rapport['annee_universitaire'], 5, 4);
$stmt->execute([$rapport['id_departement'], $rapport['annee_universitaire'], $rapport['semestre'], $annee_debut, $annee_fin]);
$affectations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer détails des UE vacantes pour ce rapport
$stmt = $pdo->prepare("
    SELECT 
        ue.code, ue.intitule, 
        f.nom as nom_filiere,
        uev.type_cours, uev.volume_horaire,
        uev.date_declaration
    FROM unites_enseignement_vacantes uev
    JOIN unites_enseignement ue ON uev.id_unite_enseignement = ue.id
    JOIN filieres f ON uev.id_filiere = f.id
    WHERE uev.id_departement = ? 
    AND uev.semestre = ?
    ORDER BY ue.code, f.nom
");
$stmt->execute([$rapport['id_departement'], $rapport['semestre']]);
$details_vacants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Détails du Rapport d'Affectation";
include 'header.php';
?>

<div class="main-container">
    <div class="content">
        <div class="container-fluid">
            <a href="generations_rapports_decisions.php" class="btn btn-secondary no-print" style="margin-bottom: 20px;">
                <i class="fas fa-arrow-left"></i> Retour à la liste des rapports
            </a>
            
            <div class="card">
                <div class="rapport-header">
                    <div class="rapport-title">
                        Rapport d'affectation - <?= htmlspecialchars($rapport['annee_universitaire']) ?> - Semestre <?= htmlspecialchars($rapport['semestre']) ?>
                    </div>
                    <div class="rapport-date">
                        Généré le <?= date('d/m/Y à H:i', strtotime($rapport['date_generation'])) ?>
                    </div>
                </div>
                
                <div class="stats-summary">
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_cm'] ?></div>
                        <div class="stat-label">Heures CM</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_td'] ?></div>
                        <div class="stat-label">Heures TD</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures_tp'] ?></div>
                        <div class="stat-label">Heures TP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['total_heures'] ?></div>
                        <div class="stat-label">Total Heures</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['nombre_enseignants'] ?></div>
                        <div class="stat-label">Enseignants</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $rapport['nombre_vacataires'] ?></div>
                        <div class="stat-label">UE Vacantes</div>
                    </div>
                </div>
                
                <!-- Section des décisions prises -->
                <div class="section-title">Décisions Prises</div>
                <?php if (empty($decisions)): ?>
                    <p>Aucune décision n'a été prise pour cette période.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type Entité</th>
                                    <th>Entité concernée</th>
                                    <th>Décisionnaire</th>
                                    <th>Ancien Statut</th>
                                    <th>Nouveau Statut</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($decisions as $decision): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($decision['date_decision'])) ?></td>
                                        <td><?= htmlspecialchars($decision['type_entite']) ?></td>
                                        <td><?= htmlspecialchars($decision['entite_concernee']) ?></td>
                                        <td><?= htmlspecialchars($decision['nom_decisionnaire'] . ' ' . $decision['prenom_decisionnaire']) ?></td>
                                        <td>
                                            <span class="decision-badge <?= 
                                                $decision['ancien_statut'] === 'approuve' || $decision['ancien_statut'] === 'validé' ? 'decision-approved' : 
                                                ($decision['ancien_statut'] === 'rejete' || $decision['ancien_statut'] === 'rejeté' ? 'decision-rejected' : 'decision-pending') 
                                            ?>">
                                                <?= htmlspecialchars($decision['ancien_statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="decision-badge <?= 
                                                $decision['nouveau_statut'] === 'approuve' || $decision['nouveau_statut'] === 'validé' ? 'decision-approved' : 
                                                ($decision['nouveau_statut'] === 'rejete' || $decision['nouveau_statut'] === 'rejeté' ? 'decision-rejected' : 'decision-pending') 
                                            ?>">
                                                <?= htmlspecialchars($decision['nouveau_statut']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($decision['commentaire']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Section des affectations -->
                <div class="section-title">Affectations</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Enseignant</th>
                                <th>UE</th>
                                <th>Filière</th>
                                <th>Type</th>
                                <th>Volume Horaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($affectations as $affectation): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($affectation['date_affectation'])) ?></td>
                                    <td><?= htmlspecialchars($affectation['nom_enseignant'] . ' ' . $affectation['prenom_enseignant']) ?></td>
                                    <td><?= htmlspecialchars($affectation['code_ue'] . ' - ' . $affectation['intitule_ue']) ?></td>
                                    <td><?= htmlspecialchars($affectation['nom_filiere']) ?></td>
                                    <td><?= htmlspecialchars($affectation['type_cours']) ?></td>
                                    <td><?= $affectation['volume_horaire'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Section des UE vacantes -->
                <div class="section-title">Unités d'enseignement déclarées vacantes</div>
                <?php if (empty($details_vacants)): ?>
                    <p>Aucune unité d'enseignement n'a été déclarée vacante pour cette période.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code UE</th>
                                    <th>Intitulé</th>
                                    <th>Filière</th>
                                    <th>Type</th>
                                    <th>Volume horaire</th>
                                    <th>Date de déclaration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details_vacants as $vacant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($vacant['code']) ?></td>
                                        <td><?= htmlspecialchars($vacant['intitule']) ?></td>
                                        <td><?= htmlspecialchars($vacant['nom_filiere']) ?></td>
                                        <td><?= htmlspecialchars($vacant['type_cours']) ?></td>
                                        <td><?= $vacant['volume_horaire'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($vacant['date_declaration'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="print-button">
                    <button class="btn btn-primary no-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        background: var(--secondary-bg);
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin: 15px 0;
        padding: 20px;
        color: var(--text-color);
    }
    
    .rapport-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .rapport-title {
        font-size: 1.5em;
        font-weight: bold;
        color: var(--accent-color);
    }
    
    .rapport-date {
        font-style: italic;
        color: var(--text-muted);
    }
    
    .stats-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        background: var(--primary-bg);
        padding: 15px;
        border-radius: 8px;
        flex: 1;
        min-width: 150px;
        text-align: center;
        border: 1px solid var(--border-color);
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 14px;
        color: var(--text-muted);
    }
    
    .section-title {
        font-size: 1.3em;
        margin: 25px 0 20px;
        color: var(--accent-color);
        border-bottom: 2px solid var(--accent-color);
        padding-bottom: 5px;
    }
    
    .table {
        color: var(--text-color);
        margin-bottom: 20px;
    }
    
    .table thead th {
        background-color: var(--primary-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }
    
    .table td {
        border-color: var(--border-color);
    }
    
    .decision-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .decision-approved {
        background-color: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }
    
    .decision-rejected {
        background-color: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }
    
    .decision-pending {
        background-color: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn i {
        font-size: 14px;
    }
    
    .btn-primary {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
    }
    
    .btn-primary:hover {
        background-color: #2a9db8;
        border-color: #2a9db8;
    }
    
    .btn-secondary {
        background-color: var(--text-muted);
        border-color: var(--text-muted);
    }
    
    .btn-secondary:hover {
        background-color: #5a6b8c;
        border-color: #5a6b8c;
    }
    
    @media print {
        .no-print {
            display: none !important;
        }
        
        .card {
            box-shadow: none;
            border: none;
        }
        
        .table {
            border-color: #000;
        }
        
        .table th,
        .table td {
            border-color: #000;
        }
    }
</style>