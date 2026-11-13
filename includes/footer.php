</main>
</div>
<footer class="app-footer border-top py-3 mt-auto">
  <div class="container-fluid px-3 small text-muted d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; <?= date('Y') ?> ConsultEase</span>
    <span>Web Systems Technology Final Project</span>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($useCharts)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.js"></script>
<?php endif; ?>
<script type="module" src="js/app.js?v=20261113-2"></script>
</body>
</html>
