<?php // FILE: application/views/marketing/analytics.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Marketing Analytics</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / Analytics</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Send Volume — Last 30 Days</div>
    <div class="card-body">
        <?php if (empty($trend)): ?>
            <p class="text-muted text-center py-4 mb-0">No sends recorded in the last 30 days yet.</p>
        <?php else: ?>
            <canvas id="trendChart" height="80"></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Campaign Comparison</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Campaign</th><th>Status</th><th class="text-right">Recipients</th>
                        <th class="text-right">Sent</th><th class="text-right">Delivered</th><th class="text-right">Read</th>
                        <th class="text-right">Failed</th><th class="text-right">Replied</th>
                        <th class="text-right">Converted</th><th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($comparison)): foreach ($comparison as $c):
                    $delivery_rate = $c->sent_count > 0 ? round($c->delivered_count / $c->sent_count * 100) : 0;
                    $conv_rate = $c->sent_count > 0 ? round($c->converted_count / $c->sent_count * 100, 1) : 0;
                ?>
                    <tr>
                        <td><a href="<?= base_url('marketing/campaign_view/' . $c->id) ?>"><?= htmlspecialchars($c->name) ?></a></td>
                        <td><span class="badge badge-light border text-capitalize"><?= $c->status ?></span></td>
                        <td class="text-right"><?= (int)$c->total_recipients ?></td>
                        <td class="text-right"><?= (int)$c->sent_count ?></td>
                        <td class="text-right"><?= (int)$c->delivered_count ?> <span class="text-muted">(<?= $delivery_rate ?>%)</span></td>
                        <td class="text-right"><?= (int)$c->read_count ?></td>
                        <td class="text-right text-danger"><?= (int)$c->failed_count ?></td>
                        <td class="text-right text-warning"><?= (int)$c->replied_count ?></td>
                        <td class="text-right text-success"><?= (int)$c->converted_count ?> <span class="text-muted">(<?= $conv_rate ?>%)</span></td>
                        <td class="text-right">₹<?= number_format($c->revenue, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($trend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
var labels   = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r->day)), $trend)) ?>;
var sent     = <?= json_encode(array_map(fn($r) => (int)$r->sent, $trend)) ?>;
var delivered = <?= json_encode(array_map(fn($r) => (int)$r->delivered, $trend)) ?>;
var readData = <?= json_encode(array_map(fn($r) => (int)$r->read_count, $trend)) ?>;
var failed   = <?= json_encode(array_map(fn($r) => (int)$r->failed, $trend)) ?>;
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'Sent',      data: sent,      borderColor: '#0d6efd', tension: 0.3 },
            { label: 'Delivered', data: delivered, borderColor: '#198754', tension: 0.3 },
            { label: 'Read',      data: readData,  borderColor: '#20c997', tension: 0.3 },
            { label: 'Failed',    data: failed,     borderColor: '#dc3545', tension: 0.3 },
        ]
    },
    options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php endif; ?>
