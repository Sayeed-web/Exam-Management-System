<?php
session_start();
require 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'instructor':
            header('Location: instructor/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'instructor':
                    header('Location: instructor/dashboard.php');
                    break;
                case 'student':
                    header('Location: student/dashboard.php');
                    break;
            }
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!-- Your existing login form HTML -->



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ExamMaster</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            dark: '#0A1A2F',    // Darker blue
                            medium: '#1E3A5F',  // Medium blue
                            light: '#2E4A7F',   // Lighter blue
                            accent: '#3B82F6'    // Bright blue accent
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .login-gradient {
            background: linear-gradient(135deg, #0A1A2F 0%, #1E3A5F 100%);
        }
    </style>
</head>
<body class="min-h-screen login-gradient font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <!-- Logo and Navigation -->
        <div class="absolute top-4 left-4">
            <a href="index.php" class="flex items-center space-x-2">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-white">ExamMaster</span>
            </a>
        </div>

        <!-- Login Form -->
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-primary-dark">Welcome Back</h2>
                    <p class="text-gray-500 mt-2">Please sign in to your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username or Email</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" 
                                   name="username" 
                                   id="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   required
                                   class="focus:ring-primary-accent focus:border-primary-accent block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-sm placeholder-gray-400"
                                   placeholder="Enter your username or email">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   required
                                   class="focus:ring-primary-accent focus:border-primary-accent block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-sm placeholder-gray-400"
                                   placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" 
                                   name="remember_me" 
                                   type="checkbox"
                                   class="h-4 w-4 text-primary-accent focus:ring-primary-accent border-gray-300 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="password_reset.php" class="font-medium text-primary-accent hover:text-blue-500">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-accent hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-accent transition duration-150 ease-in-out">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Don't have an account?</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="register.php" 
                           class="w-full flex justify-center py-3 px-4 border border-primary-accent rounded-lg shadow-sm text-sm font-medium text-primary-accent hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-accent transition duration-150 ease-in-out">
                            Create new account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
