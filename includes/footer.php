<?php
/**
 * Footer Template
 * Sistem Inventori Barang Koperasi
 */
?>
            </div><!-- .container-fluid -->
        </div><!-- .content-wrapper -->
    </div><!-- .main-content -->
    
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    
    <script>
        // Initialize CSRF token globally
        const CSRF_TOKEN = '<?= generateCsrfToken() ?>';
        const BASE_URL = '<?= BASE_URL ?>';
        
        // Auto-load notifications
        $(document).ready(function() {
            loadNotifikasi();
        });
    </script>
</body>
</html>

