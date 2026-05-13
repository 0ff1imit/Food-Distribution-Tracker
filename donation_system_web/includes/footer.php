</div><!-- end main-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle for mobile
document.getElementById('sidebarToggle')?.addEventListener('click',()=>{
    document.getElementById('sidebar').classList.toggle('show');
});
// Auto-dismiss alerts
setTimeout(()=>{
    document.querySelectorAll('.alert').forEach(a=>bootstrap.Alert.getOrCreateInstance(a).close());
},4000);
</script>
</body></html>