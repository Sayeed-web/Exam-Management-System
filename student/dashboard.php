<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Include database connection
require_once '../db.php';

$student_id = $_SESSION['user_id'];
$error_message = '';

try {
    // Test database connection
    $pdo->query('SELECT 1');

    // Get user info
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Get upcoming exams
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COALESCE(es.status, 'not_started') as attempt_status,
               CASE 
                   WHEN NOW() < e.start_time THEN 'upcoming'
                   WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'ongoing'
                   ELSE 'expired'
               END as exam_status
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
        WHERE e.end_time > NOW()
        ORDER BY e.start_time ASC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent results
    $stmt = $pdo->prepare("
        SELECT 
            e.title, 
            es.total_score, 
            es.submit_time,
            e.passing_score,
            (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) * 10 as total_points
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.student_id = ? AND es.status = 'graded'
        ORDER BY es.submit_time DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error in student dashboard: " . $e->getMessage());
    $error_message = "A database error occurred. Please try again later.";
} catch(Exception $e) {
    error_log("General Error in student dashboard: " . $e->getMessage());
    $error_message = "An error occurred while loading your dashboard.";
}

// Helper function for human readable time difference
function human_time_diff($timestamp) {
    $diff = $timestamp - time();
    
    if ($diff < 0) {
        return 'past';
    } elseif ($diff < 60) {
        return $diff . ' seconds';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes';
    } elseif ($diff < 86400) {
        return round($diff / 3600) . ' hours';
    } else {
        return round($diff / 86400) . ' days';
    }
}

// Helper function for status colors
function get_status_color($status) {
    $colors = [
        'not_started' => 'bg-yellow-100 text-yellow-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'submitted' => 'bg-green-100 text-green-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}

// Helper function for status text
function get_status_text($status) {
    $text = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted'
    ];
    return $text[$status] ?? 'Unknown';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Exam System</title>
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
                <p class="text-sm text-gray-500">Student Portal</p>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                <a href="view_exams.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Available Exams
                </a>
                <a href="view_results.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    My Results
                </a>
            </nav>

            <!-- User Profile -->
            <div class="mt-auto p-4 border-t">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-medium">
                                <?php echo isset($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : 'S'; ?>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700"><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'Student'; ?></p>
                            <p class="text-xs text-gray-500">Student</p>
                        </div>
                    </div>
                    <a href="../logout.php" class="p-2 text-red-600 hover:text-red-800 rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
                <?php if ($error_message): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Welcome Header -->
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Welcome, <?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'Student'; ?>!
                    </h1>
                    <p class="text-gray-600 mt-2">View your upcoming exams and recent results</p>
                </div>

                <!-- Dashboard Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Upcoming Exams Card -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-900">Upcoming Exams</h2>
                                <a href="view_exams.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($upcoming_exams)): ?>
                                <div class="space-y-4">
                                    <?php foreach ($upcoming_exams as $exam): ?>
                                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        Starts: <?php echo date('M j, Y g:i A', strtotime($exam['start_time'])); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        Duration: <?php echo $exam['duration']; ?> minutes
                                                    </p>
                                                </div>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo get_status_color($exam['attempt_status']); ?>">
                                                    <?php echo get_status_text($exam['attempt_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-6">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming exams</h3>
                                    <p class="mt-1 text-sm text-gray-500">Check back later for new exams.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Results Card -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-900">Recent Results</h2>
                                <a href="view_results.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($recent_results)): ?>
                                <div class="space-y-4">
                                    <?php foreach ($recent_results as $result): ?>
                                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($result['title']); ?></h3>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        Submitted: <?php echo date('M j, Y g:i A', strtotime($result['submit_time'])); ?>
                                                    </p>
                                                </div>
                                                <?php
                                                $score_percentage = ($result['total_score'] / max(1, $result['total_points'])) * 100;
                                                $score_class = $score_percentage >= 70 ? 'bg-green-100 text-green-800' : 
                                                             ($score_percentage >= 50 ? 'bg-yellow-100 text-yellow-800' : 
                                                             'bg-red-100 text-red-800');
                                                ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $score_class; ?>">
                                                    <?php echo round($score_percentage, 1); ?>%
                                                </span>
                                            </div>
                                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $score_percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-6">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No results yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Complete some exams to see your results here.</p>
                                </div>
                            <?php endif; ?>
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
