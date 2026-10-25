<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Garment Barcode Tags') ?></title>
    <!-- JsBarcode library for crisp thermal SVG/Canvas barcode printing -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f4f6f9;
            color: #000;
        }
        .no-print-bar {
            background: #212529;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .btn {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 7px 16px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            justify-content: center;
        }

        /* ── Standard 50mm x 38mm Garment Tag ───────────────────────── */
        .garment-tag {
            width: 50mm;
            height: 38mm;
            background: #fff;
            border: 1px dashed #bbb;
            padding: 2mm 2.5mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .tag-store {
            font-size: 8pt;
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 0.5px solid #000;
            padding-bottom: 1px;
            line-height: 1;
        }

        .tag-title {
            font-size: 7.5pt;
            font-weight: 800;
            text-align: center;
            line-height: 1.1;
            margin-top: 1.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tag-attrs {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            line-height: 1.2;
            margin: 1px 0;
            border-top: 0.5px dotted #666;
            border-bottom: 0.5px dotted #666;
            padding: 1px 0;
        }

        .tag-attrs .attr-item {
            display: flex;
            flex-direction: column;
        }

        .tag-attrs .attr-label {
            font-size: 5.5pt;
            color: #444;
            text-transform: uppercase;
        }

        .tag-attrs .attr-val {
            font-size: 7.5pt;
            font-weight: 800;
        }

        .tag-barcode-wrap {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .tag-barcode-wrap svg {
            width: 100% !important;
            max-height: 13mm !important;
        }

        .tag-footer {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            line-height: 1;
            margin-top: 1px;
        }

        .tag-style-code {
            font-size: 5.5pt;
            font-weight: bold;
            color: #333;
            max-width: 25mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tag-mrp {
            font-size: 9pt;
            font-weight: 900;
            text-align: right;
        }

        .tag-mrp span {
            font-size: 6pt;
            font-weight: normal;
        }

        /* ── Thermal Print Media Styles ───────────────────────────────── */
        @media print {
            body {
                background: transparent;
            }
            .no-print-bar {
                display: none !important;
            }
            .tags-container {
                padding: 0;
                gap: 0;
            }
            .garment-tag {
                border: none;
                margin: 0;
                page-break-after: always;
            }
            @page {
                size: 50mm 38mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Garment Barcode Tags Printing</strong> &bull; Total Labels: <?= count($tags) * $copies ?>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <label style="font-size: 13px; margin: 0;">Copies per variant: 
                <input type="number" id="inpCopies" value="<?= $copies ?>" min="1" max="50" style="width: 50px; padding: 4px; text-align: center; border-radius: 3px; border: 1px solid #ccc;" onchange="updateCopies(this.value)">
            </label>
            <button onclick="window.print()" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print Barcode Labels
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
    </div>

    <div class="tags-container">
        <?php 
        $store_name = $this->session->userdata('company_name') ?: 'SUVASAN CLOTHES';
        foreach ($tags as $tag): 
            for ($c = 0; $c < $copies; $c++):
        ?>
            <div class="garment-tag">
                <!-- Store Header -->
                <div class="tag-store"><?= htmlspecialchars($store_name) ?></div>

                <!-- Product Name -->
                <div class="tag-title" title="<?= htmlspecialchars($tag->product_name) ?>">
                    <?= htmlspecialchars($tag->product_name) ?>
                </div>

                <!-- Size, Colour, Category attributes -->
                <div class="tag-attrs">
                    <div class="attr-item">
                        <span class="attr-label">Colour</span>
                        <span class="attr-val"><?= htmlspecialchars(strtoupper($tag->colour_name)) ?></span>
                    </div>
                    <div class="attr-item" style="text-align: center;">
                        <span class="attr-label">Size</span>
                        <span class="attr-val" style="font-size: 8pt;"><?= htmlspecialchars($tag->size_name) ?></span>
                    </div>
                    <div class="attr-item" style="text-align: right;">
                        <span class="attr-label">Category</span>
                        <span class="attr-val"><?= htmlspecialchars($tag->garment_category ?: 'Garment') ?></span>
                    </div>
                </div>

                <!-- Barcode SVG -->
                <div class="tag-barcode-wrap">
                    <svg class="barcode-svg" data-barcode="<?= htmlspecialchars($tag->barcode) ?>"></svg>
                </div>

                <!-- Footer: Style / Article & MRP -->
                <div class="tag-footer">
                    <div class="tag-style-code" title="Article: <?= htmlspecialchars($tag->mfr_article_no ?: $tag->style_code) ?>">
                        <?= htmlspecialchars($tag->style_code ?: $tag->design_no ?: $tag->variant_sku) ?>
                    </div>
                    <div class="tag-mrp">
                        <span>MRP:</span> ₹<?= number_format((float)$tag->mrp, 2) ?>
                    </div>
                </div>
            </div>
        <?php 
            endfor;
        endforeach; 
        ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Render all SVG barcodes cleanly
            document.querySelectorAll('.barcode-svg').forEach(function(el) {
                const code = el.getAttribute('data-barcode');
                if (code) {
                    try {
                        JsBarcode(el, code, {
                            format: code.length === 13 && /^\d+$/.test(code) ? "EAN13" : "CODE128",
                            width: 1.3,
                            height: 28,
                            displayValue: true,
                            fontSize: 9,
                            margin: 0,
                            fontOptions: "bold"
                        });
                    } catch(e) {
                        // Fallback to Code 128
                        try {
                            JsBarcode(el, code, {
                                format: "CODE128",
                                width: 1.2,
                                height: 26,
                                displayValue: true,
                                fontSize: 9,
                                margin: 0
                            });
                        } catch(err) {
                            console.error(err);
                        }
                    }
                }
            });
        });

        function updateCopies(val) {
            const url = new URL(window.location.href);
            url.searchParams.set('copies', val);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
