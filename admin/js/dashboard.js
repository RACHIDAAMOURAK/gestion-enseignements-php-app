document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const departmentFilter = document.getElementById('departmentFilter');
    const userTable = document.querySelector('.user-table tbody');
    const userRows = Array.from(userTable.getElementsByTagName('tr'));

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value.toLowerCase();
        const selectedDepartment = departmentFilter.value.toLowerCase();

        userRows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const role = row.querySelector('.role-badge').textContent.toLowerCase();
            const department = row.querySelector('td:nth-child(5)').textContent.toLowerCase();

            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = selectedRole === '' || role === selectedRole;
            const matchesDepartment = selectedDepartment === '' || department === selectedDepartment;

            row.style.display = matchesSearch && matchesRole && matchesDepartment ? '' : 'none';
        });

        updateStatistics();
    }

    function updateStatistics() {
        const visibleRows = userRows.filter(row => row.style.display !== 'none');
        const activeUsers = visibleRows.filter(row => 
            row.querySelector('.status-badge').classList.contains('active')).length;
        
        document.getElementById('totalUsers').textContent = visibleRows.length;
        document.getElementById('activeUsers').textContent = activeUsers;
        document.getElementById('inactiveUsers').textContent = visibleRows.length - activeUsers;
    }

    // Fonction pour mettre à jour l'URL avec les paramètres de filtrage
    function updateURL() {
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('search', searchInput.value);
        searchParams.set('role', roleFilter.value);
        searchParams.set('department', departmentFilter.value);
        searchParams.set('page', '1'); // Retour à la première page lors d'un filtrage
        
        window.location.href = `${window.location.pathname}?${searchParams.toString()}`;
    }

    // Fonction pour initialiser les filtres depuis l'URL
    function initializeFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);
        
        const search = params.get('search');
        if (search) searchInput.value = search;
        
        const role = params.get('role');
        if (role) roleFilter.value = role;
        
        const department = params.get('department');
        if (department) departmentFilter.value = department;
    }

    // Event listeners avec délai pour la recherche
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(updateURL, 500); // Délai de 500ms
    });

    // Event listeners pour les filtres
    roleFilter.addEventListener('change', updateURL);
    departmentFilter.addEventListener('change', updateURL);

    // Initialiser les filtres au chargement de la page
    initializeFiltersFromURL();

    // Event listeners
    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);
    departmentFilter.addEventListener('change', filterTable);

    // Initialize statistics
    updateStatistics();
}); 