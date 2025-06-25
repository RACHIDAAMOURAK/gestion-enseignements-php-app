<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inclure les fichiers nécessaires
include_once '../config/database.php';
include_once '../classes/DepartmentManager.php';
include_once '../classes/SessionManager.php';
include_once '../includes/header.php';

// Instancier la base de données et les gestionnaires
$database = new Database();
$db = $database->getConnection();
$departmentManager = new DepartmentManager($db);
$sessionManager = new SessionManager();

// Récupérer les paramètres de pagination et de recherche
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Récupérer les statistiques et les départements
$stats = $departmentManager->getDepartmentStatistics();
$departments = $departmentManager->getAllDepartments($currentPage, $search);
$totalDepartments = $departmentManager->getTotalDepartments($search);
$totalPages = ceil($totalDepartments / 10);

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => 'Action non reconnue'];
        
        switch ($_POST['action']) {
            case 'add_department':
                $response = $departmentManager->addDepartment(
                    $_POST['nom'],
                    $_POST['description']
                );
                break;
                
            case 'edit_department':
                $response = $departmentManager->updateDepartment(
                    $_POST['department_id'],
                    $_POST['nom'],
                    $_POST['description']
                );
                break;
                
            case 'delete_department':
                $response = $departmentManager->deleteDepartment($_POST['department_id']);
                break;
                
            case 'add_program':
                $response = $departmentManager->addProgram(
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['department_id']
                );
                break;
                
            case 'edit_program':
                $response = $departmentManager->updateProgram(
                    $_POST['program_id'],
                    $_POST['nom'],
                    $_POST['description']
                );
                break;
                
            case 'delete_program':
                $response = $departmentManager->deleteProgram($_POST['program_id']);
                break;
        }
        
        // Stocker le message dans la session au lieu de rediriger
        $_SESSION['message'] = $response['message'];
        $_SESSION['message_status'] = $response['success'] ? 'success' : 'error';
        
        // Rediriger avec JavaScript
        echo "<script>window.location.href = 'departments.php';</script>";
        exit;
    }
}

// Afficher le message s'il existe
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $status = $_SESSION['message_status'];
    unset($_SESSION['message']);
    unset($_SESSION['message_status']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Départements - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --primary-bg: #1B2438;
            --secondary-bg: #1F2B47;
            --accent-color: #31B7D1;
            --text-color: #FFFFFF;
            --text-muted: #7086AB;
            --border-color: #2A3854;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .main-content {
            padding: 1.5rem;
        }

        .stats-card {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .stats-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
       
        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 1;
        }

        #searchInput {
            background-color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--primary-bg);
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            width: 100%;
            font-size: 1rem;
        }

        #searchInput::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }

        #searchInput:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(49, 183, 209, 0.1);
        }

        .table-container {
            background-color: var(--secondary-bg);
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .table {
            color: var(--text-color);
            margin-bottom: 0;
        }

        .table thead th {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 1rem;
        }

        .table tbody td {
            border-color: var(--border-color);
            padding: 1rem;
            vertical-align: middle;
        }
     
        .department-icon {
            background-color: rgba(49, 183, 209, 0.1);
            border-radius: 0.5rem;
            color: var(--accent-color);
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-count {
            background-color: rgba(49, 183, 209, 0.1);
            color: var(--accent-color);
            border-radius: 1rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .action-btn {
            background-color: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent-color);
        }

        .btn-add-user {
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add-user:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .pagination {
            margin-top: 1.5rem;
        }

        .page-link {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
        }

        .page-link:hover, .page-item.active .page-link {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }

        .modal-content {
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .form-control {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-color);
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background-color: var(--primary-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
            box-shadow: none;
        }

        .form-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .btn-close {
            color: var(--text-color);
        }

        .btn-secondary {
            background-color: rgba(112, 134, 171, 0.2);
            border: none;
            color: var(--text-color);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border: none;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="content">
                    <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Gestion des Départements</h4>
                        <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                            <i class="fas fa-plus"></i>
                            Ajouter un département
                        </button>
                    </div>

                    <!-- Statistics -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stats['total_departments']; ?></div>
                                <div class="stats-label">Départements</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stats['total_programs']; ?></div>
                                <div class="stats-label">Filières</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stats['total_heads']; ?></div>
                                <div class="stats-label">Chefs de département</div>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Rechercher un département...">
                            </div>
                        </div>
                    </div>

                    <!-- Departments Table -->
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>DÉPARTEMENT</th>
                                    <th>DESCRIPTION</th>
                                    <th>FILIÈRES</th>
                                    <th>UTILISATEURS</th>
                                    <th class="text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $dept): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="department-icon me-2">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <?php echo htmlspecialchars($dept['nom']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($dept['description']); ?></td>
                                        <td>
                                            <span class="badge-count">
                                                <?php echo $dept['nombre_filieres']; ?> filière(s)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-count">
                                                <?php echo $dept['nombre_utilisateurs']; ?> utilisateur(s)
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button class="action-btn" onclick="viewPrograms(<?php echo $dept['id']; ?>)" title="Voir les filières">
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button class="action-btn" onclick="editDepartment(<?php echo $dept['id']; ?>)" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" onclick="deleteDepartment(<?php echo $dept['id']; ?>)" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-end">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($currentPage - 1); ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($currentPage + 1); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
            </div>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un département</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_department">
                        <div class="mb-3">
                            <label class="form-label">Nom du département</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-check"></i>
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le département</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_department">
                        <input type="hidden" name="department_id" id="edit_department_id">
                        <div class="mb-3">
                            <label class="form-label">Nom du département</label>
                            <input type="text" class="form-control" name="nom" id="edit_department_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_department_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Programs Modal -->
    <div class="modal fade" id="programsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filières du département</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn-action" onclick="showAddProgramForm()">
                            <i class="fas fa-plus"></i>
                            Ajouter un programme
                        </button>
                    </div>
                    <div id="programsList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Program Modal -->
    <div class="modal fade" id="programFormModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="programFormTitle">Ajouter une filière</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="program_action" value="add_program">
                        <input type="hidden" name="department_id" id="program_department_id">
                        <input type="hidden" name="program_id" id="edit_program_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de la filière</label>
                            <input type="text" class="form-control" name="nom" id="program_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="program_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn-action" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="btn-action">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser la valeur du champ de recherche depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('tbody tr');
            searchInput.value = urlParams.get('search') || '';

            // Fonction de recherche dynamique
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const departmentName = row.querySelector('td:first-child').textContent.toLowerCase();
                    const description = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    
                    const matchesSearch = departmentName.includes(searchTerm) || 
                                       description.includes(searchTerm);
                    
                    row.style.display = matchesSearch ? '' : 'none';
                });
            }

            // Gestionnaire d'événement pour la recherche
            searchInput.addEventListener('input', filterTable);

            // Fonctions existantes pour la gestion des départements et des filières
            function viewPrograms(departmentId) {
                fetch(`get_programs.php?department_id=${departmentId}`)
                    .then(response => response.json())
                    .then(programs => {
                        const programsList = document.getElementById('programsList');
                        programsList.innerHTML = programs.length ? generateProgramsTable(programs) : '<p>Aucune filière trouvée</p>';
                        document.getElementById('program_department_id').value = departmentId;
                        new bootstrap.Modal(document.getElementById('programsModal')).show();
                    });
            }

            function generateProgramsTable(programs) {
                return `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NOM</th>
                                <th>DESCRIPTION</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${programs.map(program => `
                                <tr>
                                    <td>${program.nom}</td>
                                    <td>${program.description || ''}</td>
                                    <td class="text-end">
                                        <button class="action-btn" onclick="editProgram(${program.id})" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn" onclick="deleteProgram(${program.id})" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function editDepartment(departmentId) {
                fetch(`get_department.php?id=${departmentId}`)
                    .then(response => response.json())
                    .then(department => {
                        document.getElementById('edit_department_id').value = department.id;
                        document.getElementById('edit_department_name').value = department.nom;
                        document.getElementById('edit_department_description').value = department.description || '';
                        new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
                    });
            }

            function deleteDepartment(departmentId) {
                if (confirm('Êtes-vous sûr de vouloir supprimer ce département ?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_department">
                        <input type="hidden" name="department_id" value="${departmentId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }

            function showAddProgramForm() {
                document.getElementById('programFormTitle').textContent = 'Ajouter une filière';
                document.getElementById('program_action').value = 'add_program';
                document.getElementById('program_name').value = '';
                document.getElementById('program_description').value = '';
                document.getElementById('edit_program_id').value = '';
                new bootstrap.Modal(document.getElementById('programFormModal')).show();
            }

            function editProgram(programId) {
                fetch(`get_program.php?id=${programId}`)
                    .then(response => response.json())
                    .then(program => {
                        document.getElementById('programFormTitle').textContent = 'Modifier la filière';
                        document.getElementById('program_action').value = 'edit_program';
                        document.getElementById('edit_program_id').value = program.id;
                        document.getElementById('program_name').value = program.nom;
                        document.getElementById('program_description').value = program.description || '';
                        new bootstrap.Modal(document.getElementById('programFormModal')).show();
                    });
            }

            function deleteProgram(programId) {
                if (confirm('Êtes-vous sûr de vouloir supprimer cette filière ?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_program">
                        <input type="hidden" name="program_id" value="${programId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }

            // Rendre les fonctions disponibles globalement
            window.viewPrograms = viewPrograms;
            window.editDepartment = editDepartment;
            window.deleteDepartment = deleteDepartment;
            window.showAddProgramForm = showAddProgramForm;
            window.editProgram = editProgram;
            window.deleteProgram = deleteProgram;
        });
    </script>
</body>
</html>