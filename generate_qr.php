<?php
// generate_qr.php
require_once 'config.php';
requireLogin();

$id = isset($_GET['id']) ? sanitize($_GET['id']) : 0;

// Get employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: employees.php");
    exit();
}

$employee = $result->fetch_assoc();
$stmt->close();

$qr_url = BASE_URL . '/view_employee.php?employee_id=' . urlencode($employee['employee_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($employee['full_name']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 20px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; text-align: center; }
        .subtitle { color: #666; margin-bottom: 30px; text-align: center; }
        .employee-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .info-row { display: flex; margin-bottom: 10px; }
        .info-label { width: 100px; color: #666; }
        .info-value { flex: 1; color: #333; font-weight: 500; }
        .qr-container { text-align: center; margin: 30px 0; padding: 30px; background: #f9f9f9; border-radius: 15px; }
        #qrcode { display: inline-block; padding: 10px; background: white; border-radius: 10px; }
        .qr-url { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 8px; word-break: break-all; font-size: 12px; color: #666; }
        .actions { display: flex; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .password-note { background: #e8f5e8; border-left: 4px solid #4caf50; padding: 15px; margin-bottom: 30px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📱 QR Code</h1>
        <div class="subtitle"><?php echo htmlspecialchars($employee['full_name']); ?></div>
        
        <div class="employee-info">
            <div class="info-row">
                <span class="info-label">Employee ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Position:</span>
                <span class="info-value"><?php echo htmlspecialchars($employee['position'] ?: 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                <span class="info-value"><?php echo htmlspecialchars($employee['department'] ?: 'N/A'); ?></span>
            </div>
        </div>
        
        <div class="password-note">
            <strong><i class="fas fa-lock"></i> Password Protected:</strong> When scanned, users will need to enter this employee's password to view information.<br>
            <strong>Current Password:</strong> <?php echo htmlspecialchars(!empty($employee['qr_password']) ? $employee['qr_password'] : $employee['last_name'] . 'North'); ?>
        </div>
        
        <div class="qr-container">
            <div id="qrcode"></div>
            <div class="qr-url"><?php echo $qr_url; ?></div>
        </div>
        
        <div class="actions">
            <button onclick="downloadQR()" class="btn btn-primary">
                <i class="fas fa-download"></i> Download QR
            </button>
            <button onclick="printQR()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="qr_codes.php" class="btn btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none;">
                ← Back to QR Codes
            </a>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $qr_url; ?>",
            width: 250,
            height: 250
        });
        
        function downloadQR() {
            const canvas = document.querySelector("#qrcode canvas");
            if (canvas) {
                const link = document.createElement("a");
                link.download = "<?php echo preg_replace('/[^a-z0-9]/i', '_', $employee['full_name']); ?>_QR.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
            }
        }
        
        function printQR() {
            const canvas = document.querySelector("#qrcode canvas");
            if (canvas) {
                const win = window.open('');
                win.document.write('<html><head><title>QR Code - <?php echo addslashes($employee['full_name']); ?></title>');
                win.document.write('<style>body { text-align: center; padding: 50px; } img { max-width: 400px; } .info { margin: 20px; }</style>');
                win.document.write('</head><body>');
                win.document.write('<h2><?php echo addslashes($employee['full_name']); ?></h2>');
                win.document.write('<p>Employee ID: <?php echo $employee['employee_id']; ?></p>');
                win.document.write('<img src="' + canvas.toDataURL("image/png") + '">');
                win.document.write('<p>Scan to view employee information</p>');
                win.document.write('<p style="color: #666; font-size: 12px;">Password protected - contact admin for access</p>');
                win.document.write('</body></html>');
                win.print();
            }
        }
    </script>
</body>
</html>