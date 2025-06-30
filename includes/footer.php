    </div> <!-- End Main Wrapper -->

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-code me-2"></i>
                        PasteForge &copy; <?php echo date('Y'); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Built with PHP & Bootstrap 5
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Prism.js for Syntax Highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <!-- Vanta.js Background Animation -->
    <script>
    // Initialize Vanta.js background when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof VANTA !== 'undefined' && typeof THREE !== 'undefined') {
            // Check current theme
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            
            VANTA.CELLS({
                el: "#vanta-bg",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 1.00,
                color1: isDark ? 0x1e293b : 0xf8fafc,
                color2: isDark ? 0x3b82f6 : 0x6366f1,
                size: 1.50,
                speed: 1.00
            });
        }
    });
    </script>
    
    </div> <!-- End Vanta Background Container -->
</body>
</html>
