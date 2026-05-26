<?php
// qr_codes.php
require_once 'config.php';
requireLogin();

// Get all employees
$result = $conn->query("SELECT id, employee_id, first_name, last_name, full_name, position, department FROM employees ORDER BY first_name");
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

// Generate QR code URL
$base_url = BASE_URL . '/view_employee.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 14px; opacity: 0.8; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; color: white; text-decoration: none; transition: all 0.3s; }
        .menu-item i { width: 25px; margin-right: 10px; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.2); }
        .menu-item.logout { position: absolute; bottom: 20px; width: 100%; border-top: 1px solid rgba(255,255,255,0.1); }
        
        /* Main Content */
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); }
        .header { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; font-size: 24px; }
        .header .actions { display: flex; gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-secondary { background: #f0f0f0; color: #333; }
        
        .password-note { background: #e8f5e8; border-left: 4px solid #4caf50; padding: 15px; margin-bottom: 30px; border-radius: 8px; }
        
        .employee-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .employee-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .employee-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .card-header { margin-bottom: 20px; }
        .employee-name { font-size: 18px; font-weight: bold; color: #333; }
        .employee-id { font-size: 12px; color: #999; margin-top: 3px; }
        .employee-position { font-size: 14px; color: #d81919; margin-top: 5px; }
        .qr-container { text-align: center; margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 10px; }
        .qr-url { font-size: 11px; color: #666; word-break: break-all; margin-top: 10px; padding: 5px; background: #fff; border-radius: 4px; }
        .qr-actions { display: flex; gap: 10px; margin-top: 15px; }
        .qr-actions button { flex: 1; padding: 8px; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; background: #d81919; color: white; transition: background 0.3s; }
        .qr-actions button:hover { background: #5a6fd6; }
        .employee-details { margin-top: 15px; font-size: 13px; color: #666; }
        .employee-details div { margin-bottom: 3px; }
        
        @media print {
            .sidebar, .header, .actions, .password-note, .qr-actions { display: none; }
            .main-content { margin-left: 0; width: 100%; }
            .employee-card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> NSIAI</h2>
            <p>Employee Management System</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="employees.php" class="menu-item">
                <i class="fas fa-users"></i> Employees
            </a>
            <a href="add_employee.php" class="menu-item">
                <i class="fas fa-user-plus"></i> Add Employee
            </a>
            <a href="qr_codes.php" class="menu-item active">
                <i class="fas fa-qrcode"></i> QR Codes
            </a>
            <a href="records.php" class="menu-item">
                <i class="fas fa-table"></i> Records
            </a>
            <a href="reset_password.php" class="menu-item">
                <i class="fas fa-key"></i> Reset Password
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>QR Code Generator</h1>
            <div class="actions">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print All
                </button>
                <button onclick="downloadAllQRs()" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download All
                </button>
            </div>
        </div>

        <div class="password-note">
            <strong><i class="fas fa-lock"></i> Password Protection:</strong> When scanned, users will need to enter the admin password to view the information.
        </div>

        <div class="employee-grid" id="employeeGrid">
            <?php foreach ($employees as $emp): ?>
                <?php 
                $qrUrl = $base_url . '?employee_id=' . urlencode($emp['employee_id']);
                $employeeName = htmlspecialchars($emp['full_name']);
                $employeeId = htmlspecialchars($emp['employee_id']);
                ?>
                <div class="employee-card" id="card-<?php echo $emp['id']; ?>">
                    <div class="card-header">
                        <div class="employee-name"><?php echo $employeeName; ?></div>
                        <div class="employee-id">ID: <?php echo $employeeId; ?></div>
                        <div class="employee-position"><?php echo htmlspecialchars($emp['position'] ?: 'No Position'); ?></div>
                    </div>
                    
                    <div class="qr-container">
                        <div id="qrcode-<?php echo $emp['id']; ?>"></div>
                        <div class="qr-url"><?php echo $qrUrl; ?></div>
                    </div>
                    
                    <div class="employee-details">
                        <div>🏢 <?php echo htmlspecialchars($emp['department'] ?: 'No Department'); ?></div>
                    </div>
                    
                    <div class="qr-actions">
                        <button onclick="downloadQR('<?php echo $emp['id']; ?>', '<?php echo $employeeName; ?>')">Download QR</button>
                        <button onclick="copyQRUrl('<?php echo $emp['employee_id']; ?>')">Copy URL</button>
                        <button onclick="testQR('<?php echo $emp['employee_id']; ?>')">Test QR</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const baseUrl = '<?php echo $base_url; ?>';
        // Password is stored server-side only
        
        // Generate QR codes on page load
        window.onload = function() {
            <?php foreach ($employees as $emp): ?>
                <?php 
                $qrUrl = $base_url . '?employee_id=' . urlencode($emp['employee_id']);
                ?>
                try {
                    new QRCode(document.getElementById("qrcode-<?php echo $emp['id']; ?>"), {
                        text: "<?php echo $qrUrl; ?>",
                        width: 180,
                        height: 180
                    });
                } catch(e) {
                    console.error("Failed to generate QR for <?php echo $emp['id']; ?>:", e);
                }
            <?php endforeach; ?>
        };
        
        function downloadQR(employeeId, employeeName) {
            const canvas = document.querySelector("#qrcode-" + employeeId + " canvas");
            if (canvas) {
                const link = document.createElement("a");
                link.download = (employeeName || "employee").replace(/[^a-z0-9]/gi, "_") + "_QR.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
            } else {
                alert("QR code not found. Please refresh the page.");
            }
        }
        
        function downloadAllQRs() {
            const cards = document.querySelectorAll('.employee-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    const id = card.id.replace('card-', '');
                    const name = card.querySelector('.employee-name').textContent;
                    downloadQR(id, name);
                }, index * 500);
            });
        }
        
        function copyQRUrl(employeeId) {
            const url = baseUrl + "?employee_id=" + encodeURIComponent(employeeId);
            navigator.clipboard.writeText(url).then(() => {
                alert("QR URL copied to clipboard!\n\n" + url);
            }).catch(() => {
                prompt("Copy this URL:", url);
            });
        }
        
        function testQR(employeeId) {
            window.open(baseUrl + "?employee_id=" + encodeURIComponent(employeeId), "_blank");
        }
    </script>
</body>
</html>