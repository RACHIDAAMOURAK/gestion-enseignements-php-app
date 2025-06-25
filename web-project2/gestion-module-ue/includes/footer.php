</div><!-- /.content -->
        </div><!-- /.container-fluid -->
    </div><!-- /.main-container -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>
                &copy; <?php echo date('Y'); ?> Système de Gestion des UE et Groupes
                <span>|</span>
                <span style="color: var(--text-muted);">Version 1.0.0</span>
            </p>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle menu avec animation fluide
            const menuToggle = document.getElementById('menu-toggle');
            
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('menu-expanded');
                
                // Sauvegarder l'état du menu dans le localStorage
                localStorage.setItem('menuExpanded', document.body.classList.contains('menu-expanded'));
            });

            // Restaurer l'état du menu au chargement de la page
            const menuExpanded = localStorage.getItem('menuExpanded') === 'true';
            if (menuExpanded) {
                document.body.classList.add('menu-expanded');
            }

            // Animation des badges de notification
            const badges = document.querySelectorAll('.notification-badge');
            badges.forEach(badge => {
                const count = parseInt(badge.textContent);
                if (count > 0) {
                    setInterval(() => {
                        badge.style.transform = 'scale(1.2)';
                        setTimeout(() => {
                            badge.style.transform = 'scale(1)';
                        }, 200);
                    }, 3000);
                }
            });

            // Fermer automatiquement les alertes après 5 secondes
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });

            // Script spécifique pour le menu des vacataires
            const vacataireMenu = document.getElementById('vacataire-menu');
            const vacataireSubmenu = document.getElementById('vacataire-submenu');
            
            if (vacataireMenu && vacataireSubmenu) {
                // Supprimer tout gestionnaire d'événements existant
                const newVacataireMenu = vacataireMenu.cloneNode(true);
                vacataireMenu.parentNode.replaceChild(newVacataireMenu, vacataireMenu);
                
                // Ajouter un nouveau gestionnaire d'événements
                newVacataireMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.toggle('open');
                    vacataireSubmenu.classList.toggle('show');
                });
            }

            // Gestion des autres sous-menus
            const menuItems = document.querySelectorAll('.menu-item.has-submenu:not(#vacataire-menu)');
            
            menuItems.forEach(item => {
                // Supprimer tout gestionnaire d'événements existant
                const newItem = item.cloneNode(true);
                item.parentNode.replaceChild(newItem, item);
                
                // Ajouter un nouveau gestionnaire d'événements
                newItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Basculer la classe open
                    this.classList.toggle('open');
                    
                    // Trouver et basculer le sous-menu
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('show');
                    }
                });
            });

            // Vérifier s'il y a un élément actif dans un sous-menu
            const activeSubmenuItems = document.querySelectorAll('.submenu-item.active');
            activeSubmenuItems.forEach(item => {
                const submenu = item.closest('.submenu');
                if (submenu) {
                    submenu.classList.add('show');
                    const parentMenuItem = submenu.previousElementSibling;
                    if (parentMenuItem && parentMenuItem.classList.contains('has-submenu')) {
                        parentMenuItem.classList.add('open');
                    }
                }
            });
        });
    </script>
</body>
</html> 