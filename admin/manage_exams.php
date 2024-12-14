<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get all exams with instructor information
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        u.username as instructor_name,
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
        (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.id) as submission_count
    FROM exams e
    LEFT JOIN users u ON e.instructor_id = u.id
    ORDER BY e.created_at DESC
");
$stmt->execute();
$exams = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center hover:bg-gray-50 px-3 py-2 rounded-md">
                        <i class="fas fa-arrow-left text-gray-500 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Back to Dashboard</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500">Welcome, Admin</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manage Exams</h2>
                    <p class="mt-1 text-sm text-gray-600">Overview of all examinations</p>
                </div>
                <a href="create_exam.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                    <i class="fas fa-plus mr-2"></i>Create New Exam
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($exams as $exam): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-book text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($exam['title']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-tie text-gray-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($exam['instructor_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-question-circle text-indigo-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo $exam['question_count']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-users text-green-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo $exam['submission_count']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo $exam['start_time']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-red-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo $exam['end_time']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view_exam_details.php?id=<?php echo $exam['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 mr-2">
                                        <i class="fas fa-eye mr-1"></i>
                                        Details
                                    </a>
                                    <a href="view_results.php?exam_id=<?php echo $exam['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 rounded-md hover:bg-green-100">
                                        <i class="fas fa-chart-bar mr-1"></i>
                                        Results
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
