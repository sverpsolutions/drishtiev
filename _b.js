
                var ST_CSRF = ['0', '0'];
                // Correct (exact total) or remove a count from the summary — stock take still in progress
                function stFix(btn, mode) {
                    var td = btn.closest('td'), pid = td.dataset.pid, name = td.dataset.name, body = new URLSearchParams({ stock_take_id: 0, product_id: pid });
                    if (mode === 'clear') { if (!confirm('Remove the count for "' + name + '"? It goes back to NOT COUNTED.')) return; body.append('clear', '1'); }
                    else { var cur = td.querySelector('.cq').textContent.trim(), v = prompt('Correct TOTAL counted qty for "' + name + '":', cur === '—' ? '' : cur.replace(/,/g, ''));
                        if (v === null || v.trim() === '' || isNaN(parseFloat(v)) || parseFloat(v) < 0) return; body.append('qty', parseFloat(v)); }
                    body.append(ST_CSRF[0], ST_CSRF[1]);
                    fetch(0, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() })
                        .then(function (r) { var t = r.headers.get('X-CSRF-TOKEN'); if (t) ST_CSRF[1] = t; return r.json(); })
                        .then(function (res) { if (!res.success) return alert(res.message || 'Could not change the count'); location.reload(); })
                        .catch(function () { alert('Network error — the count was not changed'); });
                }
                