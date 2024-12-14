<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

// Get all exam results for the student
$stmt = $pdo->prepare("
    SELECT 
        e.id as exam_id,
        e.title as exam_title,
        e.duration,
        e.passing_score,
        es.submit_time,
        es.total_score,
        es.status,
        u.username as instructor_name,
        (SELECT SUM(points) FROM questions WHERE exam_id = e.id) as total_possible
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON e.instructor_id = u.id
    WHERE es.student_id = ?
    ORDER BY es.submit_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll();

// Calculate statistics
$total_exams = count($results);
$completed_exams = count(array_filter($results, fn($r) => $r['status'] == 'graded'));
$total_score = array_sum(array_column($results, 'total_score'));
$total_possible = array_sum(array_column($results, 'total_possible'));
$average_percentage = $total_possible > 0 ? ($total_score / $total_possible) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Portal</title>
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
                        <a href="view_exams.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Available Exams
                        </a>
                        <a href="view_results.php" class="border-b-2 border-blue-500 text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">My Exam Results</h1>
            <p class="mt-1 text-sm text-gray-500">View all your exam attempts and scores</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Exams</h3>
                <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo $total_exams; ?></div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500">Completed</h3>
                <div class="mt-2 text-3xl font-bold text-gray-900"><?php echo $completed_exams; ?></div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Score</h3>
                <div class="mt-2 text-3xl font-bold text-gray-900">
                    <?php echo $total_score; ?><span class="text-lg text-gray-500">/<?php echo $total_possible; ?></span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500">Average</h3>
                <div class="mt-2 text-3xl font-bold <?php echo $average_percentage >= 70 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo number_format($average_percentage, 1); ?>%
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Exam
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Submission Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Score
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Percentage
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($results as $result): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($result['exam_title']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        By <?php echo htmlspecialchars($result['instructor_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($result['submit_time'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $result['duration']; ?> minutes
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $result['total_score']; ?>/<?php echo $result['total_possible']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $percentage = $result['total_possible'] > 0 
                                        ? ($result['total_score'] / $result['total_possible']) * 100 
                                        : 0;
                                    $percentageClass = $percentage >= $result['passing_score'] 
                                        ? 'text-green-600' 
                                        : 'text-red-600';
                                    ?>
                                    <span class="text-sm font-medium <?php echo $percentageClass; ?>">
                                        <?php echo number_format($percentage, 1); ?>%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $result['status'] == 'graded' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($result['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_result_details.php?exam_id=<?php echo $result['exam_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($results)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
                    <p class="mt-1 text-sm text-gray-500">You haven't taken any exams yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
