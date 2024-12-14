<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header('Location: manage_users.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT *, COALESCE(status, 1) as status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: manage_users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? 1 : 0;
    $new_password = trim($_POST['new_password']);

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Check if username is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Username already taken");
        }

        // Check if email is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Email already taken");
        }

        // Update user details
        $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ?";
        $params = [$username, $email, $role, $status];

        // Add password to update if provided
        if (!empty($new_password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();
        $success = "User updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT *, COALESCE(status, 1) as status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        /* Custom switch styling */
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
            background-color: #ccc;
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
            background-color: #3b82f6;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <!-- Back Navigation -->
        <a href="dashboard.php" class="inline-flex items-center text-gray-600 hover:text-gray-800 mb-6">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Dashboard
        </a>

        <!-- Main Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Edit User</h2>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="p-4 bg-green-50 border-l-4 border-green-500">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="p-6 space-y-6">
                <!-- Username -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Username</label>
                    <input type="text" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Email -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Role -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Role</label>
                    <select name="role" 
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <div class="flex items-center space-x-3 mt-1">
                        <label class="toggle-switch">
                            <input type="checkbox" name="status" value="1" <?php echo $user['status'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-sm text-gray-600">
                            <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>

                <!-- New Password -->
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" 
                           name="new_password" 
                           minlength="6"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Leave blank to keep current password">
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-4 pt-4">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>
                        Update User
                    </button>
                    
                    <a href="view_user.php?id=<?php echo $user_id; ?>" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>

                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="delete_user.php?id=<?php echo $user_id; ?>" 
                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Delete User
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
