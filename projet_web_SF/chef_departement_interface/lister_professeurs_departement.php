<?php
// Protection du fichier et gestion de session
// Démarrer une session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simulation temporaire d'un chef connecté
if (!isset($_SESSION['id_utilisateur'])) {
    $_SESSION['id_utilisateur'] = 4;
    $_SESSION['role'] = 'chef_departement';
    $_SESSION['id_departement'] = 1;
    $_SESSION['nom_utilisateur'] = 'Chef';
}

// Vérification des permissions
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'chef_departement') {
    echo "Accès refusé.";
    exit();
}

// Définir que ce fichier est inclus pour les autres fichiers
define('INCLUDED', true);

// Connexion à la base de données
include 'db.php';
$pdo = connectDB();

$id_departement = $_SESSION['id_departement'];

// Requête pour récupérer les enseignants du département
$query = "SELECT id, nom_utilisateur, prenom, email, specialite, role 
          FROM utilisateurs 
          WHERE role IN ('enseignant', 'coordonnateur') 
          AND id_departement = ? AND actif = 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_departement]);
$enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Déterminer la page active pour le menu
$current_page = basename($_SERVER['PHP_SELF']);

// Inclure le header (avec la barre de navigation supérieure)
include 'header.php';

// Nous ne incluons plus sidebar.php car il cause un double menu
// Le menu est déjà inclus dans header.php
?>

<!-- Contenu spécifique à la page -->
<div class="main-container">
    <div class="content">
        <h2><i class="fas fa-users"></i> Liste des Enseignants de votre Département</h2>

<!-- CSS spécifique à cette page -->
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
                --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                --border-radius: 8px;
                --transition: all 0.3s ease;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Roboto', Arial, sans-serif;
                background-color: var(--primary-bg);
                color: var(--text-color);
                line-height: 1.6;
            }
            
            /* Content wrapper */
            .content {
                padding: 1.5rem;
                flex: 1;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Heading styles */
            h2 {
                color: var(--text-color);
                margin-bottom: 1.5rem;
                font-size: 1.75rem;
                font-weight: 600;
                position: relative;
                padding-bottom: 0.5rem;
                letter-spacing: 0.5px;
            }
            
            h2:after {
                content: '';
                position: absolute;
                left: 0;
                bottom: 0;
                height: 3px;
                width: 50px;
                background: var(--accent-color);
                border-radius: 3px;
            }
            
            /* Table styles */
    .table-container {
        background-color: var(--secondary-bg);
                border-radius: var(--border-radius);
        overflow: hidden;
                padding: 1.5rem;
                margin-top: 1.5rem;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow);
                transition: var(--transition);
            }
            
            .table-container:hover {
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
                transform: translateY(-2px);
    }
    
    table {
        width: 100%;
                border-collapse: separate;
                border-spacing: 0;
    }
    
    th, td {
                padding: 1rem;
        text-align: left;
                border: none;
                border-bottom: 1px solid var(--border-color);
    }
    
    th {
        background-color: rgba(49, 183, 209, 0.1);
        color: var(--text-muted);
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
            }
            
            tr:last-child td {
                border-bottom: none;
    }
    
    tr:hover {
        background-color: rgba(49, 183, 209, 0.05);
    }
    
            /* Search styles */
            .search-wrapper {
                background-color: var(--secondary-bg);
                padding: 1.25rem;
                border-radius: var(--border-radius);
                margin-bottom: 1.5rem;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow);
                transition: var(--transition);
    }
    
            .search-wrapper:hover {
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
                transform: translateY(-2px);
    }
    
            .search-wrapper input {
        width: 100%;
                padding: 0.75rem 1rem;
                background-color: rgba(27, 36, 56, 0.8);
        border: 1px solid var(--border-color);
                border-radius: var(--border-radius);
        color: var(--text-color);
                font-size: 0.95rem;
                transition: var(--transition);
    }
    
            .search-wrapper input:focus {
        outline: none;
        border-color: var(--accent-color);
                box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
            }
            
            /* Teacher info cards */
            .teachers-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
                margin-top: 1.5rem;
            }
            
            .teacher-card {
                background-color: var(--secondary-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                border: 1px solid var(--border-color);
                transition: var(--transition);
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
            }
            
            .teacher-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            }
            
            .teacher-header {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid var(--border-color);
            }
            
            .teacher-avatar {
                width: 50px;
                height: 50px;
                background-color: rgba(49, 183, 209, 0.15);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 1rem;
                color: var(--accent-color);
                font-size: 1.5rem;
            }
            
            .teacher-name {
                flex: 1;
            }
            
            .teacher-name h3 {
                margin: 0;
                color: var(--accent-color);
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .teacher-name p {
                margin: 0;
                color: var(--text-muted);
                font-size: 0.9rem;
            }
            
            .teacher-details {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 0.5rem;
            }
            
            .teacher-detail {
                display: flex;
                align-items: center;
            }
            
            .teacher-detail i {
                width: 20px;
                margin-right: 0.75rem;
                color: var(--accent-color);
            }
            
            .teacher-detail span {
                flex: 1;
            }
            
            .badge {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.75rem;
                border-radius: 50px;
                font-size: 0.8rem;
                font-weight: 600;
                margin-right: 0.5rem;
                margin-top: 0.75rem;
            }
            
            .badge-role {
                background-color: rgba(49, 183, 209, 0.15);
                color: var(--accent-color);
                border: 1px solid var(--accent-color);
            }
            
            .badge-specialite {
                background-color: rgba(92, 107, 192, 0.15);
                color: var(--info-color);
                border: 1px solid var(--info-color);
            }
            
            .badge i {
                margin-right: 0.4rem;
                font-size: 0.8rem;
            }
            
            .stats-container {
                background-color: var(--secondary-bg);
                border-radius: var(--border-radius);
                padding: 1.25rem;
                border: 1px solid var(--border-color);
                box-shadow: var(--shadow);
                margin-bottom: 1.5rem;
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .stat-item {
                flex: 1;
                min-width: 150px;
                padding: 1rem;
                background-color: rgba(27, 36, 56, 0.5);
                border-radius: var(--border-radius);
                border: 1px solid var(--border-color);
                display: flex;
                flex-direction: column;
                align-items: center;
        text-align: center;
    }
            
            .stat-value {
                font-size: 1.75rem;
                font-weight: 700;
                color: var(--accent-color);
                margin-bottom: 0.35rem;
            }
            
            .stat-label {
                font-size: 0.9rem;
                color: var(--text-muted);
            }
            
            .no-result {
                text-align: center;
                padding: 3rem;
                color: var(--text-muted);
                border: 1px dashed var(--border-color);
                border-radius: var(--border-radius);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                background-color: rgba(42, 56, 84, 0.3);
            }
            
            .no-result i {
                font-size: 2.5rem;
                color: var(--text-muted);
                opacity: 0.7;
            }
            
            .no-result p {
                font-size: 1.1rem;
            }
            
            .filter-container {
                display: flex;
                gap: 1rem;
                margin-top: 1rem;
                flex-wrap: wrap;
            }
            
            .filter-btn {
                padding: 0.5rem 1rem;
                background-color: rgba(27, 36, 56, 0.8);
                border: 1px solid var(--border-color);
                border-radius: var(--border-radius);
                color: var(--text-muted);
                cursor: pointer;
                font-size: 0.9rem;
                transition: var(--transition);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .filter-btn:hover, .filter-btn.active {
                background-color: rgba(49, 183, 209, 0.15);
                color: var(--accent-color);
                border-color: var(--accent-color);
            }
            
            .search-info {
                margin-top: 0.5rem;
                font-size: 0.85rem;
                color: var(--text-muted);
    }
    
    .highlight {
                background-color: rgba(49, 183, 209, 0.3);
                color: var(--text-color);
                padding: 0 2px;
                border-radius: 2px;
                font-weight: normal;
            }
            
            /* Responsive styles */
            @media (max-width: 992px) {
                .teachers-grid {
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                }
                
                .stat-item {
                    min-width: 120px;
                }
            }
            
            @media (max-width: 768px) {
                .content {
                    padding: 1rem;
                }
                
                .teachers-grid {
                    grid-template-columns: 1fr;
                }
                
                .table-container {
                    padding: 1rem;
                    overflow-x: auto;
                }
                
                th, td {
                    padding: 0.75rem;
                }
            }
            
            .view-toggle {
                display: flex;
                margin-bottom: 1rem;
                background-color: var(--secondary-bg);
                border-radius: var(--border-radius);
                padding: 0.5rem;
                width: fit-content;
                border: 1px solid var(--border-color);
            }
            
            .view-btn {
                padding: 0.5rem 1rem;
                cursor: pointer;
                border-radius: var(--border-radius);
                background: none;
                border: none;
                color: var(--text-muted);
                display: flex;
                align-items: center;
                gap: 0.5rem;
                transition: var(--transition);
            }
            
            .view-btn.active {
                background-color: rgba(49, 183, 209, 0.15);
        color: var(--accent-color);
            }
            
            .view-btn:hover:not(.active) {
                background-color: rgba(49, 183, 209, 0.05);
    }
    
    table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: var(--secondary-bg);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px;
            text-align: left;
            color: var(--text-color);
        }

        th {
            background-color: var(--primary-bg);
            color: var(--text-muted);
        }

        

        tr:hover {
            background-color: rgba(49, 183, 209, 0.1);
        }
</style>

        <!-- Zone de recherche et filtres -->
<div class="search-wrapper">
            <input type="text" id="searchInput" placeholder="Rechercher un enseignant par nom, rôle ou spécialité..." aria-label="Rechercher">
            <div id="searchInfo" class="search-info"></div>
            
            <div class="filter-container">
                <button class="filter-btn active" data-filter="all"><i class="fas fa-users"></i> Tous</button>
                <button class="filter-btn" data-filter="enseignant"><i class="fas fa-chalkboard-teacher"></i> Enseignants</button>
                <button class="filter-btn" data-filter="coordonnateur"><i class="fas fa-user-tie"></i> Coordonnateurs</button>
                
                <?php
                // Récupérer les spécialités uniques pour créer des filtres supplémentaires
                $specialites = [];
                foreach ($enseignants as $ens) {
                    if (!empty($ens['specialite']) && !in_array($ens['specialite'], $specialites)) {
                        $specialites[] = $ens['specialite'];
                    }
                }
                
                // Afficher un bouton de filtre pour chaque spécialité
                foreach ($specialites as $specialite): ?>
                    <button class="filter-btn" data-filter-specialite="<?= htmlspecialchars($specialite) ?>">
                        <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($specialite) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-container">
            <?php
            // Calcul des statistiques
            $total_enseignants = count($enseignants);
            $total_coordonnateurs = 0;
            $total_enseignants_simples = 0;
            $specialites_count = count($specialites);
            
            foreach ($enseignants as $ens) {
                if ($ens['role'] === 'coordonnateur') {
                    $total_coordonnateurs++;
                } else {
                    $total_enseignants_simples++;
                }
            }
            ?>
            
            <div class="stat-item">
                <div class="stat-value"><?= $total_enseignants ?></div>
                <div class="stat-label">Enseignants au total</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-value"><?= $total_coordonnateurs ?></div>
                <div class="stat-label">Coordonnateurs</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-value"><?= $total_enseignants_simples ?></div>
                <div class="stat-label">Enseignants simples</div>
</div>

            <div class="stat-item">
                <div class="stat-value"><?= $specialites_count ?></div>
                <div class="stat-label">Spécialités</div>
            </div>
        </div>

        <!-- Boutons pour basculer entre les vues -->
        <div class="view-toggle">
            <button id="gridViewBtn" class="view-btn active"><i class="fas fa-th"></i> Vue en cartes</button>
            <button id="tableViewBtn" class="view-btn"><i class="fas fa-table"></i> Vue en tableau</button>
        </div>

        <!-- Vue en cartes -->
        <div id="gridView" class="teachers-grid">
            <?php if (!empty($enseignants)): ?>
                <?php foreach ($enseignants as $ens): ?>
                    <div class="teacher-card" 
                         data-role="<?= htmlspecialchars($ens['role']) ?>" 
                         data-specialite="<?= htmlspecialchars($ens['specialite']) ?>">
                        <div class="teacher-header">
                            <div class="teacher-avatar">
                                <i class="fas <?= $ens['role'] === 'coordonnateur' ? 'fa-user-tie' : 'fa-user-graduate' ?>"></i>
                            </div>
                            <div class="teacher-name">
                                <h3><?= htmlspecialchars($ens['nom_utilisateur']) ?></h3>
                                <p><?= htmlspecialchars($ens['prenom']) ?></p>
                            </div>
                        </div>
                        <div class="teacher-details">
                            <div class="teacher-detail">
                                <i class="fas fa-id-badge"></i>
                                <span>ID: <?= htmlspecialchars($ens['id']) ?></span>
                            </div>
                            <div class="teacher-detail">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($ens['email']) ?></span>
                            </div>
                            <div class="badges">
                                <span class="badge badge-role">
                                    <i class="fas <?= $ens['role'] === 'coordonnateur' ? 'fa-user-tie' : 'fa-chalkboard-teacher' ?>"></i>
                                    <?= ucfirst(htmlspecialchars($ens['role'])) ?>
                                </span>
                                
                                <?php if (!empty($ens['specialite'])): ?>
                                    <span class="badge badge-specialite">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?= htmlspecialchars($ens['specialite']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-result">
                    <i class="fas fa-user-slash"></i>
                    <p>Aucun enseignant trouvé pour ce département</p>
                </div>
            <?php endif; ?>
            
            <!-- Message si aucun résultat de recherche n'est trouvé -->
            <div id="noResults" class="no-result" style="display: none; grid-column: 1/-1;">
                <i class="fas fa-search"></i>
                <p>Aucun enseignant ne correspond à votre recherche</p>
                <span style="font-size: 0.9rem; color: var(--text-muted); text-align: center;">
                    Essayez de modifier vos critères de recherche ou de filtre
                </span>
            </div>
        </div>

        <!-- Vue en tableau -->
        <div id="tableView" class="table-container" style="display: none;">
    <?php if (!empty($enseignants)): ?>
        <table id="enseignantsTable">
            <thead>
                <tr>
                            <th><i class="fas fa-id-badge"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nom</th>
                            <th><i class="fas fa-user"></i> Prénom</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-user-tag"></i> Rôle</th>
                            <th><i class="fas fa-graduation-cap"></i> Spécialité</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enseignants as $ens): ?>
                            <tr data-role="<?= htmlspecialchars($ens['role']) ?>" 
                                data-specialite="<?= htmlspecialchars($ens['specialite']) ?>">
                        <td><?= htmlspecialchars($ens['id']) ?></td>
                        <td><?= htmlspecialchars($ens['nom_utilisateur']) ?></td>
                        <td><?= htmlspecialchars($ens['prenom']) ?></td>
                        <td><?= htmlspecialchars($ens['email']) ?></td>
                                <td>
                                    <span class="badge badge-role">
                                        <i class="fas <?= $ens['role'] === 'coordonnateur' ? 'fa-user-tie' : 'fa-chalkboard-teacher' ?>"></i>
                                        <?= ucfirst(htmlspecialchars($ens['role'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($ens['specialite'])): ?>
                                        <span class="badge badge-specialite">
                                            <i class="fas fa-graduation-cap"></i>
                                            <?= htmlspecialchars($ens['specialite']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Non spécifiée</span>
                                    <?php endif; ?>
                                </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
                <div class="no-result">
                    <i class="fas fa-user-slash"></i>
                    <p>Aucun enseignant trouvé pour ce département</p>
                </div>
    <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script spécifique à cette page -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Éléments du DOM
        const searchInput = document.getElementById('searchInput');
        const searchInfo = document.getElementById('searchInfo');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const noResults = document.getElementById('noResults');
        const gridViewBtn = document.getElementById('gridViewBtn');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');
        
        // Éléments qui peuvent être filtrés
        const teacherCards = document.querySelectorAll('.teacher-card');
        const tableRows = document.querySelectorAll('#enseignantsTable tbody tr');
        
        let currentView = 'grid'; // grid ou table
        let activeFilter = 'all';
        let activeSpecialiteFilter = null;

        // Initialiser les écouteurs d'événements
        searchInput.addEventListener('input', applyFilters);
        
        // Changer de vue (grille ou tableau)
        gridViewBtn.addEventListener('click', function() {
            setView('grid');
        });
        
        tableViewBtn.addEventListener('click', function() {
            setView('table');
        });
        
        // Gestionnaire pour les boutons de filtre
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                const specialiteFilter = this.getAttribute('data-filter-specialite');
                
                // Désactiver tous les boutons de filtre
                filterButtons.forEach(b => b.classList.remove('active'));
                
                // Activer ce bouton
                this.classList.add('active');
                
                if (filter) {
                    activeFilter = filter;
                    activeSpecialiteFilter = null;
                } else if (specialiteFilter) {
                    activeFilter = 'all';
                    activeSpecialiteFilter = specialiteFilter;
                }
                
                applyFilters();
            });
        });

        // Fonction pour changer de vue
        function setView(view) {
            currentView = view;
            
            if (view === 'grid') {
                gridView.style.display = 'grid';
                tableView.style.display = 'none';
                gridViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
            } else {
                gridView.style.display = 'none';
                tableView.style.display = 'block';
                gridViewBtn.classList.remove('active');
                tableViewBtn.classList.add('active');
            }
            
            // Ré-appliquer les filtres pour la nouvelle vue
            applyFilters();
        }
        
        // Fonction principale pour appliquer tous les filtres
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            const totalItems = teacherCards.length;
            
            // Déterminer les éléments à filtrer en fonction de la vue actuelle
            const elementsToFilter = currentView === 'grid' ? teacherCards : tableRows;
            
            // Réinitialiser les surlignages d'abord
            elementsToFilter.forEach(el => {
                removeHighlights(el);
            });
            
            // Appliquer les filtres à tous les éléments
            elementsToFilter.forEach(el => {
                const role = el.getAttribute('data-role');
                const specialite = el.getAttribute('data-specialite');
                const elContent = el.textContent.toLowerCase();
                
                // Vérifier si l'élément correspond aux filtres actifs
                const matchesRoleFilter = activeFilter === 'all' || role === activeFilter;
                const matchesSpecialiteFilter = activeSpecialiteFilter === null || specialite === activeSpecialiteFilter;
                const matchesSearch = searchTerm === '' || elContent.includes(searchTerm);
                
                const isVisible = matchesRoleFilter && matchesSpecialiteFilter && matchesSearch;
                
                // Appliquer la visibilité
                el.style.display = isVisible ? '' : 'none';
                
                // Surligner les correspondances de recherche
                if (isVisible && searchTerm !== '') {
                    highlightMatches(el, searchTerm);
                    visibleCount++;
                } else if (isVisible) {
                    visibleCount++;
                }
            });
            
            // Mettre à jour l'information de recherche
            updateSearchInfo(visibleCount, totalItems, searchTerm);
            
            // Afficher le message "aucun résultat" si nécessaire
            if (visibleCount === 0) {
                noResults.style.display = 'flex';
            } else {
                noResults.style.display = 'none';
            }
        }

        // Fonction pour mettre à jour l'information de recherche
        function updateSearchInfo(visible, total, searchTerm) {
            if (searchTerm !== '' || activeFilter !== 'all' || activeSpecialiteFilter !== null) {
                let message = `${visible} sur ${total} enseignant${total > 1 ? 's' : ''} affichés`;
                
                if (searchTerm !== '') {
                    message += ` pour "${searchTerm}"`;
                }
                
                if (activeFilter !== 'all') {
                    message += ` - Filtre: ${activeFilter}`;
                }
                
                if (activeSpecialiteFilter !== null) {
                    message += ` - Spécialité: ${activeSpecialiteFilter}`;
                }
                
                searchInfo.textContent = message;
                searchInfo.style.display = 'block';
        } else {
                searchInfo.style.display = 'none';
            }
        }
        
        // Fonction pour surligner les correspondances dans le texte
        function highlightMatches(element, term) {
            // Fonction pour parcourir récursivement les nœuds de texte
            function processNode(node) {
                if (node.nodeType === 3) { // Nœud de texte
                    const text = node.nodeValue;
                    const lowerText = text.toLowerCase();
                    let position = lowerText.indexOf(term);
                    
                    if (position > -1) {
                        const spanNode = document.createElement('span');
                        spanNode.className = 'highlight';
                        const middleNode = document.createTextNode(text.substring(position, position + term.length));
                        spanNode.appendChild(middleNode);
                        
                        const afterNode = document.createTextNode(text.substring(position + term.length));
                        node.nodeValue = text.substring(0, position);
                        
                        const parent = node.parentNode;
                        parent.insertBefore(spanNode, node.nextSibling);
                        parent.insertBefore(afterNode, spanNode.nextSibling);
                        
                        // Continuer à chercher dans le reste du texte
                        processNode(afterNode);
                    }
                } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE' && !node.classList.contains('highlight')) {
                    // Éléments qui ne sont pas des scripts, styles ou déjà surlignés
                    Array.from(node.childNodes).forEach(child => processNode(child));
        }
    }
            
            // Commencer le traitement
            Array.from(element.childNodes).forEach(node => processNode(node));
        }
        
        // Fonction pour supprimer les surlignages
        function removeHighlights(element) {
            const highlights = element.querySelectorAll('.highlight');
            highlights.forEach(highlight => {
                const parent = highlight.parentNode;
                parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                parent.normalize(); // Fusionner les nœuds de texte adjacents
            });
        }
        
        // Initialiser l'affichage
        applyFilters();
    });
</script>

<?php
// Inclure le footer
include 'footer_chef.php';
?>