<?php
session_start();

// Check if user is logged in and is an instructor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';

$instructor_id = $_SESSION['user_id'];

try {
    // Get total exams count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_exams 
                          FROM exams 
                          WHERE instructor_id = ?");
    $stmt->execute([$instructor_id]);
    $total_exams = $stmt->fetch(PDO::FETCH_ASSOC)['total_exams'];

    // Get total submissions count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_submissions 
                          FROM exam_submissions es 
                          JOIN exams e ON es.exam_id = e.id 
                          WHERE e.instructor_id = ?");
    $stmt->execute([$instructor_id]);
    $total_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['total_submissions'];

    // Get total active students (unique students who have submitted exams)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT es.student_id) as total_students 
                          FROM exam_submissions es 
                          JOIN exams e ON es.exam_id = e.id 
                          WHERE e.instructor_id = ?");
    $stmt->execute([$instructor_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

    // Get user info
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$instructor_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error in dashboard.php: " . $e->getMessage());
    $error = "An error occurred while fetching dashboard data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Mobile Header -->
        <div class="md:hidden bg-white shadow-sm p-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-blue-600">ExamSystem</h2>
            <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <!-- Sidebar -->
        <div id="sidebar" class="hidden md:block w-full md:w-64 bg-white shadow-lg">
            <div class="p-6 border-b">
                <h2 class="text-2xl font-bold text-blue-600">ExamSystem</h2>
                <p class="text-sm text-gray-500">Instructor Portal</p>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="create_exam.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Exam
                </a>
                <a href="manage_exams.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Manage Exams
                </a>
            </nav>

            <!-- User Profile & Logout -->
            <div class="mt-auto p-4 border-t">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-medium">
                                <?php echo isset($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : 'U'; ?>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700"><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'User'; ?></p>
                            <p class="text-xs text-gray-500">Instructor</p>
                        </div>
                    </div>
                    <a href="../logout.php" class="p-2 text-red-600 hover:text-red-800 rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Instructor Dashboard</h1>
                    <p class="text-gray-600 mt-2">Manage your exams and view student progress</p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
                    <!-- Total Exams -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Total Exams</h3>
                                <p class="text-2xl font-bold text-blue-600"><?php echo isset($total_exams) ? htmlspecialchars($total_exams) : '0'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Students -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Active Students</h3>
                                <p class="text-2xl font-bold text-green-600"><?php echo isset($total_students) ? htmlspecialchars($total_students) : '0'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Submissions</h3>
                                <p class="text-2xl font-bold text-purple-600"><?php echo isset($total_submissions) ? htmlspecialchars($total_submissions) : '0'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuButton = event.target.closest('button');
            
            if (!sidebar.contains(event.target) && !mobileMenuButton && window.innerWidth < 768) {
                sidebar.classList.add('hidden');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
