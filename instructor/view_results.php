<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;

// Get exam details and results
$stmt = $pdo->prepare("
    SELECT 
        es.id as submission_id,
        u.username as student_name,
        es.submit_time,
        es.total_score,
        es.status,
        (SELECT SUM(points) FROM questions WHERE exam_id = ?) as total_possible
    FROM exam_submissions es
    JOIN users u ON es.student_id = u.id
    JOIN exams e ON es.exam_id = e.id
    WHERE es.exam_id = ? AND e.instructor_id = ?
    ORDER BY es.submit_time DESC
");
$stmt->execute([$exam_id, $exam_id, $_SESSION['user_id']]);
$results = $stmt->fetchAll();

// Get exam info
$stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ? AND instructor_id = ?");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - <?php echo htmlspecialchars($exam['title'] ?? 'Unknown Exam'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Exam Results</h2>
                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($exam['title'] ?? 'Unknown Exam'); ?></p>
                </div>
                <a href="manage_exams.php" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Exams
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Submissions -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Submissions</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900"><?php echo count($results); ?></div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Score -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Average Score</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">
                                            <?php
                                            $totalScore = 0;
                                            $gradedCount = 0;
                                            foreach ($results as $result) {
                                                if ($result['total_score'] !== null) {
                                                    $totalScore += ($result['total_score'] / $result['total_possible']) * 100;
                                                    $gradedCount++;
                                                }
                                            }
                                            echo $gradedCount > 0 ? round($totalScore / $gradedCount, 1) . '%' : 'N/A';
                                            ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Grades -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Grades</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">
                                            <?php
                                            $pending = array_filter($results, function($result) {
                                                return strtolower($result['status']) === 'pending';
                                            });
                                            echo count($pending);
                                            ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submit Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($results as $result): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($result['student_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('Y-m-d H:i', strtotime($result['submit_time'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        if ($result['total_score'] !== null) {
                                            echo $result['total_score'] . ' / ' . $result['total_possible'];
                                        } else {
                                            echo '<span class="text-gray-500">Not graded</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php
                                        if ($result['total_score'] !== null && $result['total_possible'] > 0) {
                                            $percentage = ($result['total_score'] / $result['total_possible']) * 100;
                                            $colorClass = $percentage >= 70 ? 'text-green-600' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600');
                                            echo "<span class=\"{$colorClass}\">" . round($percentage, 1) . '%</span>';
                                        } else {
                                            echo '<span class="text-gray-500">N/A</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = strtolower($result['status']) === 'pending' 
                                        ? 'bg-yellow-100 text-yellow-800' 
                                        : 'bg-green-100 text-green-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($result['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="grade_submission.php?id=<?php echo $result['submission_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">View/Grade</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <?php if (empty($results)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No submissions found</h3>
                    <p class="mt-1 text-sm text-gray-500">No students have submitted this exam yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Optional: Add sorting functionality
        function sortTable(n) {
            // Add your sorting logic here
        }

        // Optional: Add search functionality
        function searchTable() {
            // Add your search logic here
        }
    </script>
</body>
</html>
