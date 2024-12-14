<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

require '../db.php';

try {
    // Get available and completed exams with more details
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            CASE 
                WHEN NOW() < e.start_time THEN 'upcoming'
                WHEN NOW() > e.end_time THEN 'expired'
                ELSE 'available'
            END as availability,
            es.status as submission_status,
            es.total_score,
            es.submit_time,
            u.username as instructor_name,
            (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
            (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.id) as total_submissions
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
        JOIN users u ON e.instructor_id = u.id
        WHERE e.start_time >= CURDATE() - INTERVAL 30 DAY
        ORDER BY 
            CASE 
                WHEN e.start_time > NOW() THEN 1
                WHEN e.end_time > NOW() THEN 2
                ELSE 3
            END,
            e.start_time ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll();

} catch(PDOException $e) {
    $_SESSION['error'] = "Error loading exams: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams - Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="dashboard.php" class="text-xl font-bold text-blue-600">ExamSystem</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="view_exams.php" class="border-b-2 border-blue-500 text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            Available Exams
                        </a>
                        <a href="view_results.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            My Results
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-4">
                        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Available Exams</h1>
            <p class="mt-1 text-sm text-gray-500">View all upcoming and available exams</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($exams as $exam): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </h2>
                            <?php
                            $status_colors = [
                                'upcoming' => 'bg-yellow-100 text-yellow-800',
                                'available' => 'bg-green-100 text-green-800',
                                'expired' => 'bg-gray-100 text-gray-800'
                            ];
                            $status_class = $status_colors[$exam['availability']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                                <?php echo ucfirst($exam['availability']); ?>
                            </span>
                        </div>

                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Instructor:</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($exam['instructor_name']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>Duration:</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo $exam['duration']; ?> minutes
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>Questions:</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo $exam['question_count']; ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>Start Time:</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo date('M j, Y g:i A', strtotime($exam['start_time'])); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>End Time:</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo date('M j, Y g:i A', strtotime($exam['end_time'])); ?>
                                </span>
                            </div>

                            <?php if ($exam['submission_status']): ?>
                                <div class="flex justify-between">
                                    <span>Status:</span>
                                    <span class="font-medium text-gray-900">
                                        <?php echo ucfirst($exam['submission_status']); ?>
                                    </span>
                                </div>
                                <?php if ($exam['total_score'] !== null): ?>
                                    <div class="flex justify-between">
                                        <span>Score:</span>
                                        <span class="font-medium text-gray-900">
                                            <?php echo $exam['total_score']; ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6">
                            <?php if ($exam['availability'] == 'available' && !$exam['submission_status']): ?>
                                <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" 
                                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Take Exam
                                </a>
                            <?php elseif ($exam['submission_status']): ?>
                                <a href="view_results.php?exam_id=<?php echo $exam['id']; ?>" 
                                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    View Results
                                </a>
                            <?php elseif ($exam['availability'] == 'upcoming'): ?>
                                <button disabled class="w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                                    Not Yet Available
                                </button>
                            <?php else: ?>
                                <button disabled class="w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                                    Expired
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($exams)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No exams available</h3>
                <p class="mt-1 text-sm text-gray-500">Check back later for new exams.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight current navigation item
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath.split('/').pop()) {
                    link.classList.add('border-b-2', 'border-blue-500', 'text-gray-900');
                }
            });
        });
    </script>
</body>
</html>
