<?php // Location filter dropdown — include inside a <form> ?>
<div class="form-group mb-0">
    <label class="mr-1 font-weight-bold text-muted" style="font-size:0.82rem;"><i class="fas fa-store mr-1"></i>Outlet</label>
    <select name="location_id" class="form-control form-control-sm border-0 shadow-sm" style="border-radius:8px; min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
        <?php if (count($locations ?? []) > 1): ?>
        <option value="">All Outlets</option>
        <?php endif; ?>
        <?php foreach ($locations ?? [] as $loc): ?>
        <option value="<?= $loc->id ?>" <?= (isset($location_id) && $location_id == $loc->id) ? 'selected' : '' ?>>
            <?= htmlspecialchars($loc->name) ?> [<?= $loc->code ?>]
        </option>
        <?php endforeach; ?>
    </select>
</div>
