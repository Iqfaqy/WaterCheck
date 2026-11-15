<?php
/**
 * WaterCheck - Footer Layout Template
 * File: includes/footer.php
 */
?>
</div><!-- /.app-content -->

<footer class="app-footer text-center text-md-start">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div>
            <strong><?= htmlspecialchars(APP_NAME); ?></strong> &copy; <?= date('Y'); ?> &mdash; <?= htmlspecialchars(APP_TAGLINE); ?>
        </div>
        <div class="text-muted mt-2 mt-md-0">
            Versi <?= htmlspecialchars(APP_VERSION); ?> &bull; Sistem Informasi Kesehatan Lingkungan
        </div>
    </div>
</footer>

</main><!-- /.app-main -->
</div><!-- /.app-wrapper -->

<!-- Bootstrap 5.3 JS Bundle with Popper (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js 4.4 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<!-- Custom App JavaScript -->
<script src="<?= BASE_URL; ?>assets/js/script.js"></script>

<?php if (isset($extraJs)): ?>
    <?= $extraJs; ?>
<?php endif; ?>

</body>
</html>
