    </main>
    <footer>
        </div>
        <div class="footer-copy">
            &copy; <?php echo date('Y'); ?> Finance Tracker. All rights reserved.
        </div>
    </div>
    <script src="assets/js/script.js"></script>
    <?php if (isset($include_chartjs) && $include_chartjs): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</footer>
</body>
</html>
