    </div>
  </div>
</div>
<script>
// Double-submit guard + confirmation prompts for destructive actions
document.addEventListener('submit', function (e) {
  var f = e.target, c = f.getAttribute('data-confirm');
  if (c && !confirm(c)) { e.preventDefault(); return; }
  if (f.dataset.sent) { e.preventDefault(); return; }
  if ((f.method || '').toLowerCase() === 'post') f.dataset.sent = '1';
});
</script>
</body>
</html>
