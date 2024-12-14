<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$submission_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$submission_id) {
    header('Location: manage_exams.php');
    exit;
}

// Get submission details with related information
$stmt = $pdo->prepare("
    SELECT 
        es.*,
        e.title as exam_title,
        e.passing_score,
        u.username as student_name,
        i.username as instructor_name
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON es.student_id = u.id
    JOIN users i ON e.instructor_id = i.id
    WHERE es.id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    header('Location: manage_exams.php');
    exit;
}

// Get all answers with questions and options
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        q.question_text,
        q.question_type,
        q.points as max_points,
        q.correct_answer as model_answer,
        GROUP_CONCAT(
            CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct)
            SEPARATOR '|'
        ) as options
    FROM answer_submissions a
    JOIN questions q ON a.question_id = q.id
    LEFT JOIN question_options qo ON q.id = qo.question_id
    WHERE a.submission_id = ?
    GROUP BY a.id
    ORDER BY q.id
");
$stmt->execute([$submission_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submission | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .print-break-inside {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="javascript:history.back()" class="flex items-center hover:bg-gray-50 px-3 py-2 rounded-md group">
                        <i class="fas fa-arrow-left text-gray-500 mr-2 group-hover:text-blue-600"></i>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600">Back</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Submission Details</h2>
            <p class="mt-1 text-sm text-gray-600">Review student's exam submission</p>
        </div>

        <!-- General Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8 print-break-inside">
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
                                <div class="text-sm text-gray-500">Exam Title</div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($submission['exam_title']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-user text-green-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Student</div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($submission['student_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-purple-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Instructor</div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($submission['instructor_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Start Time</div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo date('Y-m-d H:i', strtotime($submission['start_time'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Submit Time</div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php 
                                    echo $submission['submit_time'] 
                                        ? date('Y-m-d H:i', strtotime($submission['submit_time']))
                                        : '<span class="text-yellow-600">In Progress</span>';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <i class="fas fa-star text-indigo-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Final Score</div>
                                <div class="text-sm font-medium">
                                    <?php if ($submission['total_score'] !== null): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $submission['total_score'] >= $submission['passing_score'] 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $submission['total_score']; ?>%
                                            <?php if ($submission['total_score'] >= $submission['passing_score']): ?>
                                                <i class="fas fa-check ml-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times ml-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Not Graded</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Answers Section -->
        <div class="space-y-6">
            <?php foreach ($answers as $answer): ?>
                <div class="bg-white rounded-lg shadow-sm p-6 print-break-inside">
                    <!-- Question -->
                    <div class="mb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-question text-blue-600 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($answer['question_text']); ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $answer['max_points']; ?> points
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Multiple Choice Answer -->
                    <?php if ($answer['question_type'] == 'multiple_choice'): ?>
                        <?php
                        $options = [];
                        if ($answer['options']) {
                            foreach (explode('|', $answer['options']) as $option) {
                                list($id, $text, $is_correct) = explode(':', $option);
                                $options[$id] = [
                                    'text' => $text,
                                    'is_correct' => $is_correct
                                ];
                            }
                        }
                        ?>
                        <div class="ml-9">
                            <div class="text-sm text-gray-700">
                                <strong>Selected Answer:</strong>
                                <?php if (isset($options[$answer['selected_option_id']])): ?>
                                    <?php $selected_option = $options[$answer['selected_option_id']]; ?>
                                    <span class="ml-2">
                                        <?php echo htmlspecialchars($selected_option['text']); ?>
                                        <?php if ($selected_option['is_correct']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                <i class="fas fa-check mr-1"></i>Correct
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                                <i class="fas fa-times mr-1"></i>Incorrect
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                    <!-- True/False Answer -->
                    <?php elseif ($answer['question_type'] == 'true_false'): ?>
                        <div class="ml-9">
                            <div class="text-sm text-gray-700">
                                <strong>Selected Answer:</strong>
                                <span class="ml-2">
                                    <?php echo $answer['answer_text']; ?>
                                    <?php if ($answer['answer_text'] == $answer['model_answer']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                            <i class="fas fa-check mr-1"></i>Correct
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                            <i class="fas fa-times mr-1"></i>Incorrect
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                    <!-- Text/Essay Answer -->
                    <?php else: ?>
                        <div class="ml-9">
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <div class="text-sm text-gray-700">
                                    <strong class="block mb-2 text-gray-600">Student's Answer:</strong>
                                    <div class="prose prose-sm max-w-none">
                                        <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($answer['model_answer']): ?>
                                <div class="mt-4 bg-blue-50 rounded-lg border border-blue-200 p-4">
                                    <div class="text-sm text-gray-700">
                                        <strong class="block mb-2 text-blue-700">Model Answer:</strong>
                                        <div class="prose prose-sm max-w-none text-blue-900">
                                            <?php echo nl2br(htmlspecialchars($answer['model_answer'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Score -->
                    <?php if ($answer['score'] !== null): ?>
                        <div class="mt-4 ml-9">
                            <div class="inline-flex items-center px-3 py-1 rounded-md
                                <?php echo ($answer['score'] == $answer['max_points']) 
                                    ? 'bg-green-100 text-green-800' 
                                    : 'bg-yellow-100 text-yellow-800'; ?>">
                                <i class="fas <?php echo ($answer['score'] == $answer['max_points']) ? 'fa-star' : 'fa-star-half-alt'; ?> mr-2"></i>
                                Score: <?php echo $answer['score']; ?> / <?php echo $answer['max_points']; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Feedback -->
                    <?php if ($answer['feedback']): ?>
                        <div class="mt-4 ml-9">
                            <div class="bg-gray-100 rounded-lg p-4 border-l-4 border-blue-500">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-comment-dots text-blue-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-gray-900">Instructor Feedback</h4>
                                        <div class="mt-1 text-sm text-gray-700">
                                            <?php echo nl2br(htmlspecialchars($answer['feedback'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex justify-between items-center no-print">
            <a href="view_exam_details.php?id=<?php echo $submission['exam_id']; ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Exam Details
            </a>

            <?php if ($submission['status'] != 'graded'): ?>
                <button type="button"
                        onclick="window.location.href='grade_submission.php?id=<?php echo $submission['id']; ?>'"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-check-circle mr-2"></i>
                    Grade Submission
                </button>
            <?php endif; ?>
        </div>

        <!-- Print Button -->
        <div class="fixed bottom-8 right-8 no-print">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-full shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-print mr-2"></i>
                Print Submission
            </button>
        </div>
    </div>
</body>
</html>


