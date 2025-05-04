<footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Base JavaScript -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    
    <!-- Custom scripts for specific pages -->
    <?php if(isset($data['js'])): ?>
        <?php foreach($data['js'] as $js): ?>
            <script src="<?= BASE_URL ?>/assets/js/<?= $js ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Close any remaining tags -->
    </body>
</html>
