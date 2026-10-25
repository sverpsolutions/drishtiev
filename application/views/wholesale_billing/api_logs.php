<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-list-alt"></i> GSP API Logs</h4>
        <a href="<?= base_url('wholesale_billing') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Timestamp</th>
                            <th>API Type</th>
                            <th>Action</th>
                            <th>Invoice</th>
                            <th>Status</th>
                            <th>Response</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No API calls logged yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log->id ?></td>
                                <td><small><?= date('d M Y H:i:s', strtotime($log->created_at)) ?></small></td>
                                <td><span class="badge badge-<?= $log->api_type === 'einvoice' ? 'primary' : 'info' ?>"><?= strtoupper($log->api_type) ?></span></td>
                                <td><small><?= htmlspecialchars($log->action ?? '') ?></small></td>
                                <td>
                                    <?php if (!empty($log->invoice_id)): ?>
                                        <a href="<?= base_url('wholesale_billing/view/' . $log->invoice_id) ?>">#<?= $log->invoice_id ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $code = (int)($log->http_status ?? 0);
                                    $cls = ($code >= 200 && $code < 300) ? 'success' : 'danger';
                                    ?>
                                    <span class="badge badge-<?= $cls ?>"><?= $code ?: 'ERR' ?></span>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars(mb_substr($log->response_payload ?? '', 0, 80)) ?><?= mb_strlen($log->response_payload ?? '') > 80 ? '...' : '' ?></small></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-secondary" onclick="toggleDetail(<?= $log->id ?>)" title="View full request/response">
                                        <i class="fas fa-code"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr id="detail_<?= $log->id ?>" style="display:none;">
                                <td colspan="8" style="background:#f8f9fa;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Request:</strong>
                                            <pre style="max-height:200px;overflow:auto;font-size:.75rem;background:#fff;padding:8px;border:1px solid #ddd;border-radius:4px;"><?= htmlspecialchars($log->request_payload ?? '-') ?></pre>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Response (HTTP <?= $code ?>):</strong>
                                            <pre style="max-height:200px;overflow:auto;font-size:.75rem;background:#fff;padding:8px;border:1px solid #ddd;border-radius:4px;"><?= htmlspecialchars($log->response_payload ?? '-') ?></pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDetail(id) {
    var row = document.getElementById('detail_' + id);
    row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
