</div> <!-- Fin de .content -->
        </div> <!-- Fin de .container-fluid -->
    </div> <!-- Fin de .main-container -->

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <span>Plateforme de Gestion Pédagogique</span> Tous droits réservés</p>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle du menu latéral
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.body.classList.toggle('menu-expanded');
        });
        
        // Gestion des sous-menus
        document.querySelectorAll('.menu-item.has-submenu').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.toggle('open');
                let submenu = this.nextElementSibling;
                if (submenu && submenu.classList.contains('submenu')) {
                    if (this.classList.contains('open')) {
                        submenu.style.display = 'block';
                    } else {
                        submenu.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>