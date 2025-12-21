            </main>
        </div>
    </div>

    <!-- padding untuk kompensasi fixed header -->
    <style>
        .content-header {
            padding-top: 10px;
        }
        .fade-in {
            padding-top: 10px;
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <?php if (isset($page_js)): ?>
    <script src="../assets/js/<?php echo $page_js; ?>"></script>
    <?php endif; ?>
    
    <!-- Initialize tooltips -->
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>