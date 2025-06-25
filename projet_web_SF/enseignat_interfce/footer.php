            </div> <!-- Fin de .content -->
        </div> <!-- Fin de .container-fluid -->
    </div> <!-- Fin de .main-container -->

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> <span>Système de gestion des départements</span> Tous droits réservés</p>
    </div>

    <script>
       document.addEventListener('DOMContentLoaded', function() {
    // 1. Gestion du menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.body.classList.toggle('menu-expanded');
            localStorage.setItem('menuExpanded', document.body.classList.contains('menu-expanded'));
        });

        // Restaurer l'état du menu
        if (localStorage.getItem('menuExpanded') === 'true') {
            document.body.classList.add('menu-expanded');
        }
    }
});
    </script>
</body>
</html> 