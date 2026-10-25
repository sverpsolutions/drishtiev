import paramiko

HOST = "82.25.106.27"
PORT = 65002
USER = "u153068796"
PASS = "Mci8yo@257"
REMOTE_BASE = "/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/"
LOCAL_BASE = r"C:\xampp\htdocs\sandalhouse\\"

FILES = [
    # DB setup script
    "po_setup.php",
    # Models
    "application/models/Supplier_model.php",
    "application/models/Purchase_order_model.php",
    "application/models/Grn_model.php",
    "application/models/Rfq_model.php",
    "application/models/Purchase_suggestion_model.php",
    # Controllers
    "application/controllers/Suppliers.php",
    "application/controllers/PurchaseOrders.php",
    "application/controllers/Grn.php",
    "application/controllers/Rfq.php",
    "application/controllers/PurchaseSuggestions.php",
    "application/controllers/VendorPortal.php",
    # Views - Purchase Orders
    "application/views/purchase_orders/_form.php",
    "application/views/purchase_orders/view.php",
    "application/views/purchase_orders/approve.php",
    "application/views/purchase_orders/print.php",
    "application/views/purchase_orders/create.php",
    "application/views/purchase_orders/edit.php",
    "application/views/purchase_orders/index.php",
    # Views - Purchase Suggestions
    "application/views/purchase_suggestions/index.php",
    # Views - GRN
    "application/views/grn/index.php",
    "application/views/grn/select_po.php",
    "application/views/grn/create.php",
    "application/views/grn/view.php",
    # Views - RFQ
    "application/views/rfq/index.php",
    "application/views/rfq/create.php",
    "application/views/rfq/view.php",
    "application/views/rfq/compare.php",
    # Views - Vendor Portal
    "application/views/vendor_portal/header.php",
    "application/views/vendor_portal/footer.php",
    "application/views/vendor_portal/login.php",
    "application/views/vendor_portal/dashboard.php",
    "application/views/vendor_portal/orders.php",
    "application/views/vendor_portal/view_po.php",
    "application/views/vendor_portal/messages.php",
    "application/views/vendor_portal/rfqs.php",
    "application/views/vendor_portal/submit_quotation.php",
    "application/views/vendor_portal/performance.php",
    # Views - Suppliers
    "application/views/suppliers/index.php",
    "application/views/suppliers/add.php",
    "application/views/suppliers/edit.php",
    "application/views/suppliers/_form.php",
    "application/views/suppliers/view.php",
    "application/views/suppliers/visit_schedule.php",
    "application/views/suppliers/products.php",
    "application/views/suppliers/documents.php",
    # Config & Layout
    "application/config/routes.php",
    "application/views/layouts/header.php",
]

def ensure_remote_dir(sftp, remote_dir):
    parts = remote_dir.replace(REMOTE_BASE.rstrip("/"), "").strip("/").split("/")
    cur = REMOTE_BASE.rstrip("/")
    for p in parts:
        if not p:
            continue
        cur += "/" + p
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)

def upload():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting to {HOST}:{PORT} ...")
    client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
    sftp = client.open_sftp()
    print("Connected.\n")

    ok = 0
    fail = 0
    for rel in FILES:
        local = LOCAL_BASE + rel.replace("/", "\\")
        remote = REMOTE_BASE + rel
        rdir = remote.rsplit("/", 1)[0]
        try:
            ensure_remote_dir(sftp, rdir)
            sftp.put(local, remote)
            print(f"  OK  {rel}")
            ok += 1
        except FileNotFoundError:
            print(f"  SKIP (local missing): {rel}")
        except Exception as e:
            print(f"  FAIL: {rel} — {e}")
            fail += 1

    sftp.close()
    client.close()
    print(f"\nDone. {ok} uploaded, {fail} failed.")

if __name__ == "__main__":
    upload()
