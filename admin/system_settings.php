<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update settings
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }
    $_SESSION['message'] = 'Settings updated successfully!';
    header('Location: system_settings.php');
    exit;
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$default_settings = [
    'site_name' => 'Online Exam System',
    'admin_email' => '',
    'max_login_attempts' => '3',
    'reset_token_expiry' => '24',
    'allow_registration' => '1',
    'maintenance_mode' => '0'
];

// Merge with defaults
$settings = array_merge($default_settings, $settings);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #2563eb;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center hover:bg-gray-50 px-3 py-2 rounded-md group">
                        <i class="fas fa-arrow-left text-gray-500 mr-2 group-hover:text-blue-600"></i>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600">Back to Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Configure your application settings</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <p class="text-green-700"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <div class="bg-white rounded-lg shadow-sm">
            <form method="POST" class="space-y-6 p-6">
                <!-- Site Name -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Site Name</label>
                    <input type="text" 
                           name="settings[site_name]" 
                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Admin Email -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                    <input type="email" 
                           name="settings[admin_email]" 
                           value="<?php echo htmlspecialchars($settings['admin_email']); ?>" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Login Attempts -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Maximum Login Attempts</label>
                    <input type="number" 
                           name="settings[max_login_attempts]" 
                           value="<?php echo htmlspecialchars($settings['max_login_attempts']); ?>" 
                           min="1" 
                           max="10" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Allowed range: 1-10 attempts</p>
                </div>

                <!-- Token Expiry -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Password Reset Token Expiry (hours)</label>
                    <input type="number" 
                           name="settings[reset_token_expiry]" 
                           value="<?php echo htmlspecialchars($settings['reset_token_expiry']); ?>" 
                           min="1" 
                           max="72" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Allowed range: 1-72 hours</p>
                </div>

                <!-- Toggle Switches -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Allow New Registrations</label>
                            <p class="text-xs text-gray-500 mt-1">Enable or disable new user registrations</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="settings[allow_registration]" 
                                   value="1" 
                                   <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Maintenance Mode</label>
                            <p class="text-xs text-gray-500 mt-1">Enable maintenance mode to temporarily disable the site</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="settings[maintenance_mode]" 
                                   value="1" 
                                   <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a href="dashboard.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add confirmation before leaving page with unsaved changes
        let formChanged = false;
        const form = document.querySelector('form');
        
        form.addEventListener('change', () => {
            formChanged = true;
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>

