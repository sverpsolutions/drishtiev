<div class="row justify-content-center mt-5">
    <div class="col-md-7 text-center">
        <div class="card border-0 shadow-sm p-5">
            <div class="mb-4">
                <i class="fas fa-hotel text-warning" style="font-size: 4rem;"></i>
            </div>
            <h3 class="font-weight-bold text-dark mb-2"><?= htmlspecialchars($module_name) ?> Module</h3>
            <p class="text-muted mb-4 lead" style="font-size: 1.1rem;">
                This hotel management screen is active in your user permission configuration.
            </p>
            <div class="alert alert-warning py-3 text-left d-inline-block mx-auto mb-4" style="max-width: 500px;">
                <i class="fas fa-info-circle mr-2"></i> 
                <strong>Permission Configured Mappings:</strong><br>
                The access checkboxes are fully functional. Your role has permission to open this module, and it is routed correctly. Mapped data hooks will load when backend hotel databases are active.
            </div>
            <div>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-dark px-4 font-weight-bold">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
