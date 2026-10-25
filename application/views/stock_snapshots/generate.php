<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-camera"></i> Generate Stock Snapshot</h3>
            </div>
            <div class="panel-body">
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>
                <?php if($message): ?>
                    <div class="alert alert-success"><?= $message; ?></div>
                <?php endif; ?>

                <div class="alert alert-warning">
                    <strong><i class="fa fa-info-circle"></i> Note:</strong> 
                    Generating a stock snapshot saves the current calculated closing stock for a specific date permanently into a high-speed table. This drastically speeds up all future stock reports that run after this date. Existing snapshots for the same Date and Store will be overwritten.
                </div>

                <form action="<?= base_url('stock_snapshots/process_generation') ?>" method="POST" id="snapshotForm">
                    <div class="form-group">
                        <label>Snapshot Date (End of Day)</label>
                        <input type="date" name="snapshot_date" class="form-control" value="<?= date('Y-m-t', strtotime('-1 month')) ?>" required>
                        <small class="text-muted">Typically the last day of the month after stock verification.</small>
                    </div>

                    <div class="form-group">
                        <label>Store / Location</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">-- Select Store --</option>
                            <?php foreach($locations as $loc): ?>
                                <option value="<?= $loc->id ?>"><?= $loc->name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Are you sure you want to generate this snapshot? It may take a few seconds to process.');">
                            <i class="fa fa-bolt"></i> Generate Snapshot
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
