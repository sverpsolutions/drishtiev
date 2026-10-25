import paramiko
import os

HOST = "82.25.106.27"
PORT = 65002
USER = "u153068796"
PASS = "Mci8yo@257"
REMOTE_BASE = "/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/"
LOCAL_BASE = r"C:\xampp\htdocs\sandalhouse\\"

FILES = [
    # Views - Purchase Orders
    "application/views/purchase_orders/_form.php",
    "application/views/purchase_orders/view.php",
    "application/views/purchase_orders/approve.php",
    "application/views/purchase_orders/print.php",
    "application/views/purchase_orders/create.php",
    "application/views/purchase_orders/edit.php",
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
    "application/views/suppliers/view.php",
    "application/views/suppliers/visit_schedule.php",
    "application/views/suppliers/products.php",
    "application/views/suppliers/documents.php",
    # Config
    "application/config/routes.php",
    # Layout
    "application/views/layouts/header.php",
]

def upload():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
    sftp = client.open_sftp()

    for rel in FILES:
        local = LOCAL_BASE + rel.replace("/", "\\")
        remote = REMOTE_BASE + rel
        rdir = remote.rsplit("/", 1)[0]
        try:
            sftp.stat(rdir)
        except FileNotFoundError:
            parts = rdir.replace(REMOTE_BASE, "").split("/")
            cur = REMOTE_BASE.rstrip("/")
            for p in parts:
                if not p:
                    continue
                cur += "/" + p
                try:
                    sftp.stat(cur)
                except:
                    sftp.mkdir(cur)
        try:
            sftp.put(local, remote)
            print(f"  OK: {rel}")
        except Exception as e:
            print(f"  FAIL: {rel} — {e}")

    sftp.close()
    client.close()
    print("\nUpload complete.")

if __name__ == "__main__":
    upload()
