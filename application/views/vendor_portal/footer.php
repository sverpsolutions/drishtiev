</div><!-- end vp-main -->
<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
(function() {
    var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
    document.querySelectorAll('form').forEach(function(form) {
        if (!form.querySelector('[name="' + csrfName + '"]')) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = csrfName; inp.value = csrfHash;
            form.appendChild(inp);
        }
    });
    window._csrfName = csrfName; window._csrfHash = csrfHash;
})();
</script>
</body></html>
