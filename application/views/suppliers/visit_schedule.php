<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-calendar-alt text-info mr-2"></i>Visit Schedule — <?= htmlspecialchars($supplier->name) ?></h5>
    <a href="<?= base_url('suppliers/view/'.$supplier->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>
<?php $flash = $this->session->flashdata('success'); if ($flash): ?><div class="alert alert-success"><?= $flash ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <?= form_open('suppliers/visit_schedule/'.$supplier->id) ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white py-2">Schedule Settings</div>
            <div class="card-body">
                <?php $sc = $schedule ?? (object)['schedule_type'=>'weekly','interval_days'=>7,'week_days'=>'[]','month_dates'=>'[]','next_visit_date'=>null]; ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Schedule Type</label>
                    <select name="schedule_type" class="form-control form-control-sm" id="schedType" onchange="toggleSched()">
                        <option value="daily" <?= ($sc->schedule_type??'')==='daily'?'selected':'' ?>>Daily</option>
                        <option value="interval" <?= ($sc->schedule_type??'')==='interval'?'selected':'' ?>>Every N Days</option>
                        <option value="weekly" <?= ($sc->schedule_type??'')==='weekly'?'selected':'' ?>>Weekly (specific days)</option>
                        <option value="monthly_dates" <?= ($sc->schedule_type??'')==='monthly_dates'?'selected':'' ?>>Monthly (specific dates)</option>
                    </select>
                </div>
                <div id="intervalDiv" style="display:none" class="form-group">
                    <label class="small font-weight-bold">Visit Every (days)</label>
                    <input name="interval_days" type="number" class="form-control form-control-sm" value="<?= $sc->interval_days??7 ?>">
                </div>
                <div id="weeklyDiv" style="display:none" class="form-group">
                    <label class="small font-weight-bold">Day(s) of week</label>
                    <?php
                    $wdays = json_decode($sc->week_days??'[]', true) ?? [];
                    $daynames = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                    foreach ($daynames as $d):
                    ?>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" id="wd_<?= $d ?>" name="week_days[]" value="<?= $d ?>" <?= in_array($d,$wdays)?'checked':'' ?>>
                        <label class="custom-control-label" for="wd_<?= $d ?>"><?= $d ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="monthDiv" style="display:none" class="form-group">
                    <label class="small font-weight-bold">Dates of month (comma-sep, e.g. 1,15)</label>
                    <?php $mdates = implode(',', json_decode($sc->month_dates??'[]', true)??[]); ?>
                    <input name="month_dates" class="form-control form-control-sm" value="<?= $mdates ?>" placeholder="1,15">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Override Next Visit Date (optional)</label>
                    <input name="next_visit_override" type="date" class="form-control form-control-sm" value="<?= $sc->next_visit_date ? date('Y-m-d',strtotime($sc->next_visit_date)) : '' ?>">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-save mr-1"></i>Save Schedule</button>
        <?= form_close() ?>

        <!-- Log Visit -->
        <div class="card border-0 shadow-sm mt-3 p-3">
            <div class="font-weight-bold mb-2">Log a Visit</div>
            <div class="form-group mb-2">
                <label class="small">Notes</label>
                <textarea id="visitNotes" class="form-control form-control-sm" rows="2" placeholder="Discussed products, agreed price..."></textarea>
            </div>
            <div class="form-group mb-2">
                <label class="small">PO Value (₹)</label>
                <input id="visitPoVal" type="number" step="0.01" class="form-control form-control-sm">
            </div>
            <button class="btn btn-sm btn-outline-success" onclick="logVisit()"><i class="fas fa-check mr-1"></i>Log Visit & Advance</button>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold">Upcoming Visits (next 30 days)</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming??[] as $d):
                        $diff = (new DateTime())->diff(new DateTime($d['date']))->days;
                        $future = (new DateTime($d['date'])) >= new DateTime();
                        $cls = $diff==0?'table-danger':($diff<=2?'table-warning':'');
                    ?>
                    <tr class="<?= $cls ?>">
                        <td><strong><?= date('d M Y',strtotime($d['date'])) ?></strong></td>
                        <td><?= date('D',strtotime($d['date'])) ?></td>
                        <td><?php if ($diff==0&&$future): ?><span class="badge badge-danger">TODAY</span><?php elseif (!$future): ?><span class="badge badge-secondary">Past</span><?php elseif ($diff<=2): ?><span class="badge badge-warning">Soon</span><?php else: ?><span class="badge badge-light">Upcoming</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($upcoming)): ?><tr><td colspan="3" class="text-center text-muted py-3">Configure schedule first.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function toggleSched() {
    var t = document.getElementById('schedType').value;
    document.getElementById('intervalDiv').style.display = t==='interval'?'':'none';
    document.getElementById('weeklyDiv').style.display   = t==='weekly'?'':'none';
    document.getElementById('monthDiv').style.display    = t==='monthly_dates'?'':'none';
}
toggleSched();

function logVisit() {
    var notes  = document.getElementById('visitNotes').value;
    var pov    = document.getElementById('visitPoVal').value;
    var fd = new FormData();
    fd.append('supplier_id', <?= $supplier->id ?>);
    fd.append('notes', notes);
    fd.append('po_value', pov);
    fetch('<?= base_url('suppliers/log_visit_ajax') ?>', {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();}).then(function(d){
        if(d.success){ alert('Visit logged! Next visit: '+d.next_visit); location.reload(); }
        else alert(d.error||'Error logging visit');
    });
}
</script>
