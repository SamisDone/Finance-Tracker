<?php
/**
 * Finance Tracker - Setup Script
 * Run this script once to set up the application
 * Access via: http://localhost/Finance-Tracker/setup.php
 */

// Check if already installed
if (file_exists(__DIR__ . '/.env')) {
    die('Application already set up. Delete this file for security.');
}

// Additional security: Check if this file is accessible via web
if (!isset($_POST['db_type'])) {
    // On first load, show warning
    $warning = '⚠️ SECURITY WARNING: Delete this file after setup!';
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_type = $_POST['db_type'] ?? 'sqlite';
    
    // Create .env file
    $env_content = "# Environment Variables for Finance Tracker\n";
    $env_content .= "DB_TYPE=" . $db_type . "\n";
    
    if ($db_type === 'mysql') {
        $env_content .= "DB_HOST=" . ($_POST['db_host'] ?? 'localhost') . "\n";
        $env_content .= "DB_USER=" . ($_POST['db_user'] ?? 'root') . "\n";
        $env_content .= "DB_PASS=" . ($_POST['db_pass'] ?? '') . "\n";
        $env_content .= "DB_NAME=" . ($_POST['db_name'] ?? 'finance_tracker_db') . "\n";
    } else {
        $env_content .= "DB_PATH=finance_tracker.db\n";
    }
    
    file_put_contents(__DIR__ . '/.env', $env_content);
    
    // Create uploads directory
    if (!is_dir(__DIR__ . '/uploads/receipts')) {
        mkdir(__DIR__ . '/uploads/receipts', 0755, true);
    }
    
    // Create assets/images/avatars directory
    if (!is_dir(__DIR__ . '/assets/images/avatars')) {
        mkdir(__DIR__ . '/assets/images/avatars', 0755, true);
    }
    
    // Show success page, then delete this file
    @unlink(__FILE__); // Delete setup file for security (suppress errors)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Finance Tracker - Setup Complete</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
            h1 { color: #22c55e; }
            .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; margin-top: 15px; }
            .btn:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Setup Complete!</h1>
            <p>Your Finance Tracker has been configured successfully.</p>
            <a href="index.php" class="btn">Go to Login / Register</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .info { background: #dbeafe; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-coins"></i> Finance Tracker Setup</h1>
        
        <?php if (isset($warning)): ?>
            <div class="alert alert-error"><?php echo $warning; ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Welcome!</strong> This setup will configure your Finance Tracker application.
            SQLite is recommended for easy deployment (no database server needed).
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Database Type:</label>
                <select name="db_type" id="db_type" onchange="toggleDbConfig()">
                    <option value="sqlite">SQLite (Recommended - Portable)</option>
                    <option value="mysql">MySQL (Requires Database Server)</option>
                </select>
            </div>
            
            <div id="mysql_config" style="display:none;">
                <div class="form-group">
                    <label>Database Host:</label>
                    <input type="text" name="db_host" value="localhost">
                </div>
                <div class="form-group">
                    <label>Database User:</label>
                    <input type="text" name="db_user" value="root">
                </div>
                <div class="form-group">
                    <label>Database Password:</label>
                    <input type="password" name="db_pass">
                </div>
                <div class="form-group">
                    <label>Database Name:</label>
                    <input type="text" name="db_name" value="finance_tracker_db">
                </div>
            </div>
            
            <button type="submit">Complete Setup</button>
        </form>
    </div>
    
    <script>
    function toggleDbConfig() {
        var dbType = document.getElementById('db_type').value;
        document.getElementById('mysql_config').style.display = dbType === 'mysql' ? 'block' : 'none';
    }
    </script>
</body>
</html>
