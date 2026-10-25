<?php // FILE: application/views/print_suite/index.php ?>
<style>
/* ── Print Suite Aesthetics ── */
.print-suite-wrapper { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.ps-header { background: linear-gradient(135deg, #0055a5 0%, #003366 100%); padding: 30px; border-radius: 8px; color: white; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.ps-title { font-size: 28px; font-weight: 700; margin: 0 0 5px 0; letter-spacing: 0.5px; }
.ps-subtitle { font-size: 15px; opacity: 0.85; margin: 0; }

.ps-filters { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; border: 1px solid #eaeaea; }
.ps-filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px; }
.ps-filter-group label { font-size: 13px; font-weight: 600; color: #444; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.ps-filter-group input, .ps-filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: all 0.3s ease; }
.ps-filter-group input:focus, .ps-filter-group select:focus { border-color: #0055a5; box-shadow: 0 0 0 3px rgba(0,85,165,0.1); }

.ps-btn-search { background: #10b981; color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.2s; height: 43px; display: flex; align-items: center; justify-content: center; gap: 8px; }
.ps-btn-search:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(16,185,129,0.2); }

/* Document Type Tabs */
.ps-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; overflow-x: auto; }
.ps-tab { padding: 8px 18px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 20px; color: #555; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.ps-tab:hover { background: #e9ecef; }
.ps-tab.active { background: #0055a5; color: white; border-color: #0055a5; box-shadow: 0 4px 8px rgba(0,85,165,0.2); }

/* Results Table */
.ps-results-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eaeaea; overflow: hidden; }
.ps-table { width: 100%; border-collapse: collapse; }
.ps-table th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 13px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #eee; }
.ps-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #333; vertical-align: middle; }
.ps-table tr:last-child td { border-bottom: none; }
.ps-table tbody tr:hover { background: #fdfdfd; }
.ps-table .doc-no { font-weight: 700; color: #0055a5; }

.ps-btn-print { background: #3b82f6; color: white; border: none; padding: 6px 15px; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.ps-btn-print:hover { background: #2563eb; color: white; text-decoration: none; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(59,130,246,0.3); }

/* Status Badges */
.ps-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.ps-badge.success { background: #d1fae5; color: #065f46; }
.ps-badge.warning { background: #fef3c7; color: #92400e; }
.ps-badge.danger { background: #fee2e2; color: #991b1b; }
.ps-badge.secondary { background: #f3f4f6; color: #374151; }

.loading-state { text-align: center; padding: 50px; color: #666; font-size: 16px; display: none; }
.empty-state { text-align: center; padding: 50px; color: #888; font-size: 16px; display: none; }
</style>

<div class="print-suite-wrapper">
    
    <div class="ps-header">
        <h1 class="ps-title"><i class="fas fa-print mr-2"></i> Print Suite</h1>
        <p class="ps-subtitle">Centralized hub for searching and printing all system documents.</p>
    </div>

    <!-- Type Selection Tabs -->
    <div class="ps-tabs" id="docTabs">
        <div class="ps-tab active" data-type="grn">Purchases (GRN)</div>
        <div class="ps-tab" data-type="prn">Purchase Returns (PRN)</div>
        <div class="ps-tab" data-type="sales">Sales Invoices</div>
        <div class="ps-tab" data-type="sales_return">Sales Returns</div>
        <div class="ps-tab" data-type="transfer">Stock Transfers</div>
    </div>

    <!-- Filters -->
    <div class="ps-filters">
        <input type="hidden" id="currentType" value="grn">
        
        <div class="ps-filter-group" style="flex: 2;">
            <label>Search Document</label>
            <input type="text" id="searchQuery" placeholder="Enter doc number, name, phone..." onkeypress="if(event.key === 'Enter') loadDocuments()">
        </div>
        
        <div class="ps-filter-group">
            <label>From Date</label>
            <input type="date" id="fromDate">
        </div>
        
        <div class="ps-filter-group">
            <label>To Date</label>
            <input type="date" id="toDate">
        </div>

        <button type="button" class="ps-btn-search" onclick="loadDocuments()">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <!-- Results Area -->
    <div class="ps-results-card">
        <div class="loading-state" id="loadingState">
            <i class="fas fa-spinner fa-spin fa-2x mb-3 text-primary"></i>
            <div>Searching documents...</div>
        </div>
        
        <div class="empty-state" id="emptyState">
            <i class="fas fa-folder-open fa-3x mb-3 text-muted" style="opacity: 0.5;"></i>
            <div>No documents found matching your criteria.</div>
        </div>

        <div class="table-responsive" id="resultsContainer" style="display: none;">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Document No.</th>
                        <th>Date</th>
                        <th>Party / Details</th>
                        <th>Amount (₹)</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                    <!-- Dynamic Rows Here -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates (last 30 days)
    let d = new Date();
    document.getElementById('toDate').value = d.toISOString().split('T')[0];
    d.setDate(d.getDate() - 30);
    document.getElementById('fromDate').value = d.toISOString().split('T')[0];

    // Tab clicking logic
    document.querySelectorAll('.ps-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.ps-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('currentType').value = this.dataset.type;
            loadDocuments();
        });
    });

    // Initial load
    loadDocuments();
});

function loadDocuments() {
    let type = document.getElementById('currentType').value;
    let query = document.getElementById('searchQuery').value;
    let from = document.getElementById('fromDate').value;
    let to = document.getElementById('toDate').value;

    document.getElementById('resultsContainer').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('loadingState').style.display = 'block';

    let formData = new FormData();
    formData.append('type', type);
    formData.append('query', query);
    formData.append('from_date', from);
    formData.append('to_date', to);

    fetch('<?= base_url("PrintSuite/search_ajax") ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingState').style.display = 'none';
        
        if (data.status === 'success' && data.data.length > 0) {
            let html = '';
            data.data.forEach(row => {
                let badgeClass = 'secondary';
                let st = row.status.toLowerCase();
                if(st === 'received' || st === 'paid' || st === 'completed' || st === 'approved') badgeClass = 'success';
                else if(st === 'partial') badgeClass = 'warning';
                else if(st === 'pending' || st === 'due') badgeClass = 'danger';

                html += `
                    <tr>
                        <td class="doc-no">${row.doc_no}</td>
                        <td>${row.doc_date}</td>
                        <td>${row.entity_name}</td>
                        <td style="font-weight:600;">${row.amount}</td>
                        <td><span class="ps-badge ${badgeClass}">${row.status}</span></td>
                        <td class="text-center">
                            <a href="${row.print_url}" target="_blank" class="ps-btn-print">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </td>
                    </tr>
                `;
            });
            document.getElementById('resultsBody').innerHTML = html;
            document.getElementById('resultsContainer').style.display = 'block';
        } else {
            document.getElementById('emptyState').style.display = 'block';
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
    });
}
</script>
