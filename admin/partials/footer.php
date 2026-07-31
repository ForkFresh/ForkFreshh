  </div><!-- /.admin-content -->
  <footer style="text-align:center;padding:14px;font-size:.78rem;color:#aaa;border-top:1px solid #e0e0e0;background:#fff;">
    &copy; <?= date('Y') ?> ForkFresh Admin Panel
  </footer>
</div><!-- /.admin-main -->
<script>
const adminSidebar = document.getElementById('adminSidebar');
const adminOverlay = document.getElementById('adminOverlay');
document.getElementById('adminToggle')?.addEventListener('click', () => {
  adminSidebar.classList.toggle('open');
  adminOverlay.classList.toggle('visible');
});
adminOverlay?.addEventListener('click', () => {
  adminSidebar.classList.remove('open');
  adminOverlay.classList.remove('visible');
});
</script>
</body>
</html>
