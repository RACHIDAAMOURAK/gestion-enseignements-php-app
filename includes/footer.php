            </div><!-- /.content -->
        </div><!-- /.container-fluid -->
    </div><!-- /.main-container -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>
                &copy; <?php echo date('Y'); ?> Système de Gestion
                <span>|</span>
                <span style="color: #8A9CC9;">Version 1.0.0</span>
            </p>
        </div>
    </footer>

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
        });
    </script>
</body>
</html> 