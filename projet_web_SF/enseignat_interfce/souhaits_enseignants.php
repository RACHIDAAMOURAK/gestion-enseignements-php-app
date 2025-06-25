<?php
session_start();

include 'db.php';
$pdo = connectDB();

// Vérifier que l'utilisateur est connecté et est enseignant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: /projet_web/login.php');
    
    exit;
}

$id_utilisateur = $_SESSION['user_id'];

// Récupérer la spécialité de l'enseignant depuis la table utilisateurs
$stmt = $pdo->prepare("SELECT specialite FROM utilisateurs WHERE id = ?");
$stmt->execute([$id_utilisateur]);
$specialite = $stmt->fetchColumn();

$message = "";
$error = "";
$voeux_soumis = false;
$seuil_minimum_horaire = 192;

// Get current academic year
$currentYear = date('Y');
$academicYear = (date('n') >= 9) ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;

// Affichage messages
if (isset($_SESSION['voeux_soumis']) && $_SESSION['voeux_soumis'] === true) {
    $voeux_soumis = true;
    unset($_SESSION['voeux_soumis']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ues']) && !empty($_POST['ues'])) {
        $ues_choisies = $_POST['ues'];
        $priorites = $_POST['priorites'];
        $commentaires = $_POST['commentaires'];
        $types = $_POST['types'];
        $filieres = $_POST['filieres'];
        
        // Vérification de l'unicité des priorités dans la soumission actuelle
        if (count(array_unique($priorites)) !== count($priorites)) {
            $_SESSION['error'] = "Chaque priorité doit être unique. Veuillez vérifier vos choix.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Vérification de l'unicité des priorités dans la base de données
        foreach ($priorites as $priorite) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM voeux_professeurs 
                WHERE id_utilisateur = ? 
                AND priorite = ?
            ");
            $stmt->execute([$id_utilisateur, $priorite]);
            $existe = $stmt->fetchColumn();
            
            if ($existe > 0) {
                $_SESSION['error'] = "La priorité $priorite est déjà utilisée dans l'un de vos vœux précédents.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }

        try {
            $pdo->beginTransaction();

            foreach ($ues_choisies as $index => $id_ue) {
                // Vérifier si la filière existe
                $stmt = $pdo->prepare("SELECT id FROM filieres WHERE id = ?");
                $stmt->execute([$filieres[$index]]);
                if (!$stmt->fetch()) {
                    throw new Exception("La filière sélectionnée n'existe pas.");
                }

                // Vérifier si ce vœu existe déjà
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM voeux_professeurs 
                    WHERE id_utilisateur = ? 
                    AND id_ue = ? 
                    AND type_ue = ?
                    AND id_filiere = ?
                ");
                $stmt->execute([$id_utilisateur, $id_ue, $types[$index], $filieres[$index]]);
                $existe = $stmt->fetchColumn();

                if (!$existe) {
                    $stmt = $pdo->prepare("
                        INSERT INTO voeux_professeurs (
                            id_utilisateur,
                            id_ue,
                            priorite,
                            commentaire,
                            type_ue,
                            id_filiere
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id_utilisateur,
                        $id_ue,
                        $priorites[$index],
                        $commentaires[$index],
                        $types[$index],
                        $filieres[$index]
                    ]);
                }
            }
            
            $pdo->commit();
            $_SESSION['message'] = "Vos vœux ont été enregistrés avec succès !";
            
            // --- Début Ajout logique notification heures insuffisantes ---
            // Recalculer le total des heures APRES l'ajout des nouveaux vœux
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(CASE 
                        WHEN vp.type_ue = 'CM' THEN ue.volume_horaire_cm 
                        WHEN vp.type_ue = 'TD' THEN ue.volume_horaire_td 
                        WHEN vp.type_ue = 'TP' THEN ue.volume_horaire_tp 
                        ELSE 0 
                    END) as total_heures
                FROM voeux_professeurs vp
                JOIN unites_enseignement ue ON vp.id_ue = ue.id
                WHERE vp.id_utilisateur = ?
            ");
            $stmt->execute([$id_utilisateur]);
            $resultat_apres_ajout = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_horaire_apres_ajout = $resultat_apres_ajout['total_heures'] ?? 0;
            
            // Vérifier le seuil minimum et créer une notification si nécessaire
            if ($total_horaire_apres_ajout < $seuil_minimum_horaire) {
                $titre = "Heures insuffisantes";
                $message_notif = "Attention: Votre charge horaire actuelle est de {$total_horaire_apres_ajout}h, ce qui est inférieur au minimum requis de {$seuil_minimum_horaire}h.";
                
                // Préparer l'insertion de la notification
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (id_utilisateur, titre, message, type, statut, date_creation)
                    VALUES (?, ?, ?, 'warning', 'non_lu', NOW())
                ");
                
                // Exécuter l'insertion de la notification
                // Note: On utilise $id_utilisateur qui est déjà défini en début de script
                $stmt->execute([$id_utilisateur, $titre, $message_notif]);
            }
            // --- Fin Ajout logique notification heures insuffisantes ---

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de la soumission : " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Récupération des UEs avec leurs filières pour la spécialité de l'enseignant
$stmt = $pdo->prepare("
    SELECT DISTINCT
           ue.id as id_ue,
           ue.code,
           ue.intitule,
           ue.semestre,
           ue.specialite,
           ue.volume_horaire_cm,
           ue.volume_horaire_td,
           ue.volume_horaire_tp,
           f.id as id_filiere,
           f.nom as nom_filiere
    FROM unites_enseignement ue
    INNER JOIN filieres f ON ue.id_filiere = f.id
    WHERE LOWER(ue.specialite) = LOWER(?)
    ORDER BY ue.intitule, f.nom
");
$stmt->execute([$specialite]);
$ues_filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organisation des UEs par combinaison UE-filière
$ues_by_filiere = [];
foreach ($ues_filieres as $ue) {
    $key = $ue['id_ue'] . '_' . $ue['id_filiere'];
    if (!isset($ues_by_filiere[$key])) {
        $ues_by_filiere[$key] = [
            'id_ue' => $ue['id_ue'],
            'id_filiere' => $ue['id_filiere'],
            'code' => $ue['code'],
            'intitule' => $ue['intitule'],
            'nom_filiere' => $ue['nom_filiere'],
            'volumes' => [
                'cm' => $ue['volume_horaire_cm'],
                'td' => $ue['volume_horaire_td'],
                'tp' => $ue['volume_horaire_tp']
            ]
        ];
    }
}

// Récupération du total des heures actuellement sélectionnées
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE 
            WHEN vp.type_ue = 'CM' THEN ue.volume_horaire_cm 
            WHEN vp.type_ue = 'TD' THEN ue.volume_horaire_td 
            WHEN vp.type_ue = 'TP' THEN ue.volume_horaire_tp 
            ELSE 0 
        END) as total_heures
    FROM voeux_professeurs vp
    JOIN unites_enseignement ue ON vp.id_ue = ue.id
    WHERE vp.id_utilisateur = ?
    AND ue.annee_universitaire = ?
");
$stmt->execute([$id_utilisateur, $academicYear]);
$resultat = $stmt->fetch(PDO::FETCH_ASSOC);
$total_heures_actuelles = $resultat['total_heures'] ?? 0;

// Récupération des types d'UE déjà choisis par l'utilisateur
$stmt = $pdo->prepare("
    SELECT id_ue, type_ue, id_filiere
    FROM voeux_professeurs 
    WHERE id_utilisateur = ?
");
$stmt->execute([$id_utilisateur]);
$types_choisis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau pour faciliter la recherche
$types_choisis_map = [];
foreach ($types_choisis as $tc) {
    $key = $tc['id_ue'] . '_' . $tc['id_filiere'];
    if (!isset($types_choisis_map[$key])) {
        $types_choisis_map[$key] = [];
    }
    $types_choisis_map[$key][] = $tc['type_ue'];
}

// Définir la page actuelle pour le sidebar
$current_page = basename(__FILE__);
?>

<?php include 'header_enseignant.php'; ?>
<?php // include 'sidebar_enseignant.php'; ?>

<div class="content-wrapper">
    <div class="form-container">
        <h2>Soumettre vos vœux (Spécialité : <?= htmlspecialchars($specialite) ?>)</h2>
        <p>Année universitaire : <?= htmlspecialchars($academicYear) ?></p>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success" id="successMessage">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" id="errorMessage">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="heures-info">
            <span>Total actuel :</span>
            <span class="heures-badge <?= $total_heures_actuelles < $seuil_minimum_horaire ? 'heures-warning' : '' ?>">
                <?= $total_heures_actuelles ?>h / <?= $seuil_minimum_horaire ?>h (minimum)
            </span>
        </div>

        <?php if ($voeux_soumis): ?>
            <div class="success" id="success-message">✅ Vœux soumis avec succès !</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error" id="error-message"><?= $error ?></div>
        <?php endif; ?>

        <div class="modules-container">
            <?php if (!empty($ues_by_filiere)): ?>
                <?php foreach ($ues_by_filiere as $key => $ue): ?>
                    <button class="module-button" id="module_<?= $key ?>"
                        onclick="toggleUEs('<?= $key ?>')">
                        <?= htmlspecialchars($ue['intitule']) ?>
                        <br>
                        <span class="filiere-badge">(<?= htmlspecialchars($ue['nom_filiere']) ?>)</span>
                    </button>
                    
                    <div id="ue-list-<?= $key ?>" class="ue-list">
                        <div class="ue-item">
                            <div>
                                <strong><?= htmlspecialchars($ue['code']) ?> - <?= htmlspecialchars($ue['intitule']) ?></strong>
                                <div class="volume-details">
                                    <?php 
                                    $types_deja_choisis = isset($types_choisis_map[$key]) ? $types_choisis_map[$key] : [];
                                    if ($ue['volumes']['cm'] > 0): 
                                        $cm_disabled = in_array('CM', $types_deja_choisis);
                                    ?>
                                        <div class="volume-option <?= $cm_disabled ? 'type-disabled' : '' ?>" id="option-<?= $key ?>-CM">
                                            <span>CM: <?= $ue['volumes']['cm'] ?>h</span>
                                            <button class="ue-select-btn" 
                                                onclick="selectUEType('<?= $key ?>', 'CM', <?= $ue['volumes']['cm'] ?>)"
                                                <?= $cm_disabled ? 'disabled' : '' ?>>
                                                <?= $cm_disabled ? 'Déjà choisi' : 'Sélectionner' ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($ue['volumes']['td'] > 0): 
                                        $td_disabled = in_array('TD', $types_deja_choisis);
                                    ?>
                                        <div class="volume-option <?= $td_disabled ? 'type-disabled' : '' ?>" id="option-<?= $key ?>-TD">
                                            <span>TD: <?= $ue['volumes']['td'] ?>h</span>
                                            <button class="ue-select-btn" 
                                                onclick="selectUEType('<?= $key ?>', 'TD', <?= $ue['volumes']['td'] ?>)"
                                                <?= $td_disabled ? 'disabled' : '' ?>>
                                                <?= $td_disabled ? 'Déjà choisi' : 'Sélectionner' ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($ue['volumes']['tp'] > 0):
                                        $tp_disabled = in_array('TP', $types_deja_choisis);
                                    ?>
                                        <div class="volume-option <?= $tp_disabled ? 'type-disabled' : '' ?>" id="option-<?= $key ?>-TP">
                                            <span>TP: <?= $ue['volumes']['tp'] ?>h</span>
                                            <button class="ue-select-btn" 
                                                onclick="selectUEType('<?= $key ?>', 'TP', <?= $ue['volumes']['tp'] ?>)"
                                                <?= $tp_disabled ? 'disabled' : '' ?>>
                                                <?= $tp_disabled ? 'Déjà choisi' : 'Sélectionner' ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <button onclick="closeUEList('<?= $key ?>')" class="close-button">Fermer</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune unité d'enseignement disponible pour votre spécialité.</p>
            <?php endif; ?>
        </div>

        <form method="POST" onsubmit="return validateForm();">
            <div id="chosen-ues"></div>
            <button type="submit" id="submit-button">Soumettre</button>
        </form>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closeAllUELists()"></div>

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

    .form-container {
        max-width: 1200px;
        background: var(--secondary-bg);
        padding: 2rem;
        margin: 2rem auto;
        border-radius: var(--card-border-radius);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: var(--text-color);
        animation: fadeIn 0.3s ease-out;
    }
    
    .form-container h2 {
        color: var(--accent-color);
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    
    .modules-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .module-button {
        background-color: var(--secondary-bg);
        color: var(--text-color);
        text-align: center;
        padding: 1.5rem;
        border-radius: var(--card-border-radius);
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
        height: 150px;
    }
    
    .module-button:hover {
        transform: var(--card-hover-transform);
        border-color: var(--accent-color);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    }
    
    .module-button:disabled {
        background-color: rgba(255, 255, 255, 0.05);
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .filiere-badge {
        font-size: 0.8em;
        background-color: rgba(49, 183, 209, 0.1);
        color: var(--accent-color);
        padding: 0.35em 0.65em;
        border-radius: 0.375rem;
        margin-top: 0.5rem;
    }
    
    .ue-list {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: var(--secondary-bg);
        padding: 2rem;
        border-radius: var(--card-border-radius);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        width: 90%;
        max-width: 600px;
        color: var(--text-color);
        max-height: 90vh;
        overflow-y: auto;
        
        /* Nouvelle gestion de l'affichage */
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .ue-item {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .volume-details {
        margin-top: 1rem;
    }
    
    .volume-option {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        margin: 0.5rem 0;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }
    
    .volume-option:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--accent-color);
    }
    
    .ue-select-btn {
        background-color: var(--accent-color);
        color: white !important;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .ue-select-btn:hover {
        background-color: rgb(171, 226, 237);
        transform: translateY(-2px);
    }
    
    .ue-select-btn:disabled {
        background-color: var(--text-muted);
        cursor: not-allowed;
    }
    
    .module-form {
        background: rgba(255, 255, 255, 0.05);
        padding: 1.5rem;
        border-radius: var(--card-border-radius);
        margin-bottom: 1.5rem;
        border-left: 3px solid var(--accent-color);
        animation: slideIn 0.3s ease-out;
    }
    
    .module-form input,
    .module-form textarea {
        margin-top: 0.5rem;
        padding: 0.75rem;
        width: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-color);
        border-radius: 0.375rem;
        color: var(--text-color);
        transition: all 0.3s ease;
    }
    
    .module-form input:focus,
    .module-form textarea:focus {
        border-color: var(--accent-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(49, 183, 209, 0.2);
    }
    
    .module-form button {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
        border: 1px solid #e74c3c;
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .module-form button:hover {
        background-color: #e74c3c;
        color: white;
    }
    
    .alert {
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: var(--card-border-radius);
        border: none;
        animation: slideDown 0.3s ease-out;
    }
    
    .alert-success {
        background-color: rgba(46, 204, 113, 0.1);
        border-left: 4px solid #2ecc71;
        color: #2ecc71;
    }
    
    .alert-error {
        background-color: rgba(231, 76, 60, 0.1);
        border-left: 4px solid #e74c3c;
        color: #e74c3c;
    }
    
    .heures-info {
        margin: 1.5rem 0;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: var(--card-border-radius);
        border: 1px solid var(--border-color);
    }
    
    .heures-badge {
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        color: white;
        background-color: var(--accent-color);
        font-weight: 600;
    }
    
    .heures-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    #submit-button {
        background-color: var(--accent-color);
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: var(--card-border-radius);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        margin-top: 1.5rem;
        display: none;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    #submit-button:hover {
        background-color: rgb(171, 226, 237);
        transform: translateY(-2px);
    }
    
    .close-button {
        background-color: var(--accent-color);
        color: white !important;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: var(--card-border-radius);
        cursor: pointer;
        margin-top: 1.5rem;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .close-button:hover {
        background-color: rgb(171, 226, 237);
        transform: translateY(-2px);
    }
    
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        backdrop-filter: blur(4px);
        
        /* Nouvelle gestion de l'affichage */
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    @keyframes fadeIn { /* Cette animation ne sera plus utilisée directement par .ue-list */
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .form-container {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .modules-container {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        
        .module-button {
            height: 120px;
            padding: 1rem;
        }
        
        .ue-list {
            width: 95%;
            padding: 1.5rem;
        }
    }
</style>

<script>
// Stocker les données des UEs pour le JavaScript
const ueData = <?= json_encode($ues_by_filiere) ?>;
const seuil_minimum_horaire = <?= $seuil_minimum_horaire ?>;
const total_heures_actuelles = <?= $total_heures_actuelles ?>;
const typesDejaChoisis = <?= json_encode($types_choisis_map) ?>;

let selectedUEs = {};
let calculatedHours = 0;
// Pour vérifier l'unicité des priorités dans le formulaire actuel
let selectedPriorities = new Set();

function toggleUEs(key) {
    const ueList = document.getElementById('ue-list-' + key);
    const overlay = document.getElementById('overlay');
    
    // Fermer toute autre fenêtre ouverte et overlay
    const allLists = document.querySelectorAll('.ue-list');
    allLists.forEach(list => {
        list.style.opacity = '0';
        list.style.visibility = 'hidden';
    });
    overlay.style.opacity = '0';
    overlay.style.visibility = 'hidden';

    // Ouvrir la fenêtre sélectionnée
    ueList.style.visibility = 'visible';
    ueList.style.opacity = '1';
    overlay.style.visibility = 'visible';
    overlay.style.opacity = '1';
}

function selectUEType(ueKey, type, volume) {
    // Vérifier si ce type est déjà choisi
    if (typesDejaChoisis[ueKey] && typesDejaChoisis[ueKey].includes(type)) {
        alert(`Le type ${type} est déjà choisi pour ce module!`);
        return;
    }
    
    // Appliquer la classe "selected-type" pour rendre transparent l'option sélectionnée
    const optionElement = document.getElementById(`option-${ueKey}-${type}`);
    if (optionElement) {
        optionElement.classList.add('selected-type');
    }
    
    if (!selectedUEs[ueKey]) {
        selectedUEs[ueKey] = {
            types: new Set(),
            totalTypes: 0,
            volumes: {}
        };
    }

    if (!selectedUEs[ueKey].types.has(type)) {
        selectedUEs[ueKey].types.add(type);
        selectedUEs[ueKey].volumes[type] = volume;
        calculatedHours += volume;
        
        const container = document.getElementById('chosen-ues');
        const formId = `ue-form-${ueKey}-${type}`;
        const div = document.createElement('div');
        div.classList.add('module-form');
        div.id = formId;
        
        const [ueId, filiereId] = ueKey.split('_');
        
        div.innerHTML = `
            <strong>${ueData[ueKey].code} - ${ueData[ueKey].intitule} (${type})</strong>
            <span class="filiere-badge">${ueData[ueKey].nom_filiere}</span>
            <div class="volume-info">Volume: ${volume}h</div>
            <input type="hidden" name="ues[]" value="${ueId}">
            <input type="hidden" name="filieres[]" value="${filiereId}">
            <input type="hidden" name="types[]" value="${type}">
            <div class="form-row">
                <label>Priorité:</label>
                <input type="number" name="priorites[]" min="1" required onchange="checkPriority(this)">
            </div>
            <div class="form-row">
                <label>Commentaire:</label>
                <textarea name="commentaires[]" rows="2"></textarea>
            </div>
            <button type="button" onclick="removeType('${ueKey}', '${type}', '${formId}', ${volume})">❌ Supprimer</button>
        `;
        
        container.appendChild(div);
        
        selectedUEs[ueKey].totalTypes++;
        
        // Désactiver le bouton de type dans la fenêtre de sélection
        const volumeOption = document.querySelector(`#ue-list-${ueKey} .volume-option button[onclick*="${type}"]`);
        if (volumeOption) {
            volumeOption.disabled = true;
            volumeOption.closest('.volume-option').classList.add('type-disabled');
        }
        
        const totalAvailableTypes = countAvailableTypes(ueKey);
        
        if (selectedUEs[ueKey].totalTypes === totalAvailableTypes) {
            const button = document.getElementById(`module_${ueKey}`);
            if (button) {
                button.style.opacity = '0.5';
                button.disabled = true;
            }
        }
        
        document.getElementById('submit-button').style.display = 'block';
        
        // Fermer la fenêtre des types après sélection
        closeUEList(ueKey);
        
        updateHoursDisplay();
    }
}

function removeType(ueKey, type, formId, volume) {
    document.getElementById(formId).remove();
    selectedUEs[ueKey].types.delete(type);
    selectedUEs[ueKey].totalTypes--;
    calculatedHours -= volume;
    
    // Réinitialiser l'apparence de l'option
    const optionElement = document.getElementById(`option-${ueKey}-${type}`);
    if (optionElement) {
        optionElement.classList.remove('selected-type');
    }
    
    const button = document.getElementById(`module_${ueKey}`);
    if (button) {
        button.style.opacity = '1';
        button.disabled = false;
    }
    
    if (document.getElementById('chosen-ues').children.length === 0) {
     document.getElementById('submit-button').style.display = 'none';
    }
    
    // Réactiver le bouton de type dans la fenêtre de sélection
    const volumeOption = document.querySelector(`#ue-list-${ueKey} .volume-option button[onclick*="${type}"]`);
    if (volumeOption) {
        volumeOption.disabled = false;
        volumeOption.closest('.volume-option').classList.remove('type-disabled');
    }
    
    updateHoursDisplay();
}

function countAvailableTypes(ueKey) {
    let count = 0;
    const ue = ueData[ueKey];
    if (ue.volumes.cm > 0) count++;
    if (ue.volumes.td > 0) count++;
    if (ue.volumes.tp > 0) count++;
    return count;
}

function updateHoursDisplay() {
    const totalHeures = total_heures_actuelles + calculatedHours;
    const heuresBadge = document.querySelector('.heures-badge');
    heuresBadge.textContent = `${totalHeures}h / ${seuil_minimum_horaire}h (minimum)`;
    heuresBadge.classList.toggle('heures-warning', totalHeures < seuil_minimum_horaire);
}

function checkPriority(input) {
    const value = input.value;
    const allPriorities = document.querySelectorAll('input[name="priorites[]"]');
    let count = 0;
    
    allPriorities.forEach(p => {
        if (p.value === value && p.value !== '') {
            count++;
        }
    });
    
    if (count > 1) {
        alert(`La priorité ${value} est déjà utilisée. Veuillez choisir une autre priorité.`);
        input.value = '';
        input.focus();
    }
}

function closeUEList(key) {
    const ueList = document.getElementById('ue-list-' + key);
    const overlay = document.getElementById('overlay');
    if (ueList) {
        ueList.style.opacity = '0';
        ueList.style.visibility = 'hidden';
    }
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.visibility = 'hidden';
    }
}

function closeAllUELists() {
    const allLists = document.querySelectorAll('.ue-list');
    allLists.forEach(list => {
        list.style.opacity = '0';
        list.style.visibility = 'hidden';
    });
    document.getElementById('overlay').style.opacity = '0';
    document.getElementById('overlay').style.visibility = 'hidden';
}

function validateForm() {
    const priorityInputs = document.querySelectorAll('input[name="priorites[]"]');
    const values = Array.from(priorityInputs).map(input => input.value);
    const unique = new Set(values);

    if (unique.size !== values.length) {
        alert("Chaque priorité doit être unique entre les unités d'enseignement choisies.");
        return false;
    }
    return true;
}

// Gestion des messages temporaires
document.addEventListener('DOMContentLoaded', function() {
    const messages = ['successMessage', 'errorMessage'];
    messages.forEach(messageId => {
        const element = document.getElementById(messageId);
        if (element) {
            setTimeout(() => {
                element.style.display = 'none';
            }, 3000);
        }
    });
});
</script>

</div> <!-- Fin de content-wrapper -->
 
</body>
</html>
