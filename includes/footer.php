    </main>
    <footer>
        <div class="footer-copy">
            &copy; <?php echo date('Y'); ?> FinPulse. All rights reserved.
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
    <?php if (isset($include_chartjs) && $include_chartjs): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</body>
</html>
