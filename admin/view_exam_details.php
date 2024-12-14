<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$exam_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$exam_id) {
    header('Location: manage_exams.php');
    exit;
}

// Get exam details
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        u.username as instructor_name,
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as question_count,
        (SELECT COUNT(*) FROM exam_submissions es WHERE es.exam_id = e.id) as submission_count
    FROM exams e
    JOIN users u ON e.instructor_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: manage_exams.php');
    exit;
}

// Get questions
$stmt = $pdo->prepare("
    SELECT 
        q.*,
        (SELECT COUNT(*) FROM question_options qo WHERE qo.question_id = q.id) as option_count
    FROM questions q
    WHERE q.exam_id = ?
    ORDER BY q.id
");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get submissions
$stmt = $pdo->prepare("
    SELECT 
        es.*,
        u.username as student_name,
        (SELECT COUNT(*) FROM answer_submissions ans WHERE ans.submission_id = es.id) as answer_count
    FROM exam_submissions es
    JOIN users u ON es.student_id = u.id
    WHERE es.exam_id = ?
    ORDER BY es.submit_time DESC
");
$stmt->execute([$exam_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Details | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="manage_exams.php" class="flex items-center hover:bg-gray-50 px-3 py-2 rounded-md group">
                        <i class="fas fa-arrow-left text-gray-500 mr-2 group-hover:text-blue-600"></i>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600">Back to Exams</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Exam Details</h2>
            <p class="mt-1 text-sm text-gray-600">Comprehensive information about the exam</p>
        </div>

        <!-- General Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">General Information</h3>
                <div class="flex space-x-2">
                    <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Exam
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-book text-blue-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Title</div>
                                <div class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($exam['title']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-green-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Instructor</div>
                                <div class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($exam['instructor_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Duration</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-clock text-blue-600 mr-2"></i>
                                <?php echo $exam['duration']; ?> minutes
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Passing Score</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-percentage text-green-600 mr-2"></i>
                                <?php echo $exam['passing_score']; ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Start Time</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-calendar text-blue-600 mr-2"></i>
                                <?php echo date('Y-m-d H:i', strtotime($exam['start_time'])); ?>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">End Time</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-calendar text-red-600 mr-2"></i>
                                <?php echo date('Y-m-d H:i', strtotime($exam['end_time'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Questions</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-question-circle text-purple-600 mr-2"></i>
                                <?php echo $exam['question_count']; ?>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Submissions</div>
                            <div class="text-lg font-medium text-gray-900">
                                <i class="fas fa-users text-indigo-600 mr-2"></i>
                                <?php echo $exam['submission_count']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Questions Section -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">Questions</h3>
        <a href="add_question.php?exam_id=<?php echo $exam['id']; ?>" 
           class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>
            Add Question
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($questions as $question): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #<?php echo $question['id']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $question['question_type'] === 'multiple_choice' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $question['points']; ?> pts
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $question['option_count']; ?> options
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Submissions Section -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Submissions</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submit Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($submissions as $submission): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($submission['student_name']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <i class="fas fa-clock text-blue-400 mr-1"></i>
                            <?php echo date('Y-m-d H:i', strtotime($submission['start_time'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($submission['submit_time']): ?>
                                <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                <?php echo date('Y-m-d H:i', strtotime($submission['submit_time'])); ?>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>
                                    In Progress
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($submission['total_score'] !== null): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $submission['total_score'] >= $exam['passing_score'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $submission['total_score']; ?>%
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Not Graded
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $submission['status'] == 'graded' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="view_submission.php?id=<?php echo $submission['id']; ?>" 
                               class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors">
                                <i class="fas fa-eye mr-1"></i>
                                View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Back Button -->
<div class="flex justify-end">
    <a href="manage_exams.php" 
       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2"></i>
        Back to Exam List
    </a>
</div>

</div>
</body>
</html>
