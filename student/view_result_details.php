<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;
$student_id = $_SESSION['user_id'];

try {
    // First, get the submission details
    $stmt = $pdo->prepare("
        SELECT es.*, e.title, e.duration, e.passing_score
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.exam_id = ? AND es.student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$exam_id, $student_id]);
    $submission = $stmt->fetch();

    if (!$submission) {
        header('Location: view_results.php');
        exit;
    }

    // Get questions with answers and options
    $stmt = $pdo->prepare("
        SELECT 
            q.id,
            q.question_text,
            q.question_type,
            q.points,
            q.correct_answer,
            ea.answer as selected_answer,
            GROUP_CONCAT(
                CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct)
                ORDER BY qo.id ASC
                SEPARATOR '|'
            ) as options
        FROM questions q
        LEFT JOIN exam_answers ea ON ea.question_id = q.id AND ea.submission_id = ?
        LEFT JOIN question_options qo ON qo.question_id = q.id
        WHERE q.exam_id = ?
        GROUP BY q.id
        ORDER BY q.id ASC
    ");
    $stmt->execute([$submission['id'], $exam_id]);
    $questions = $stmt->fetchAll();

    // Calculate statistics
    $total_questions = count($questions);
    $correct_answers = 0;
    foreach ($questions as $question) {
        if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'true_false') {
            if ($question['selected_answer'] == $question['correct_answer']) {
                $correct_answers++;
            }
        }
    }

    $accuracy = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: view_results.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Result Summary -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($submission['title']); ?>
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Submitted: <?php echo date('F j, Y g:i A', strtotime($submission['submit_time'])); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold <?php echo $submission['total_score'] >= $submission['passing_score'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($submission['total_score'], 1); ?>%
                        </div>
                        <p class="text-sm text-gray-500">Final Score</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">Questions</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            <?php echo $correct_answers; ?>/<?php echo $total_questions; ?>
                        </div>
                        <div class="text-xs text-gray-500">Correct</div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">Accuracy</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            <?php echo number_format($accuracy, 1); ?>%
                        </div>
                        <div class="text-xs text-gray-500">Overall</div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">Duration</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            <?php echo $submission['duration']; ?> min
                        </div>
                        <div class="text-xs text-gray-500">Allowed Time</div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm font-medium text-gray-500">Status</div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo $submission['total_score'] >= $submission['passing_score'] ? 
                                    'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Passing Score: <?php echo $submission['passing_score']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Review -->
        <div class="space-y-6">
            <?php foreach ($questions as $index => $question): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Question <?php echo $index + 1; ?>
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo $question['selected_answer'] == $question['correct_answer'] ? 
                                    'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $question['selected_answer'] == $question['correct_answer'] ? 'Correct' : 'Incorrect'; ?>
                            </span>
                        </div>

                        <p class="text-gray-800 mb-4"><?php echo htmlspecialchars($question['question_text']); ?></p>

                        <?php if ($question['question_type'] != 'essay'): ?>
                            <div class="space-y-2">
                                <?php 
                                $options = array_map(function($opt) {
                                    list($id, $text, $is_correct) = explode(':', $opt);
                                    return [
                                        'id' => $id,
                                        'text' => $text,
                                        'is_correct' => $is_correct
                                    ];
                                }, explode('|', $question['options']));

                                foreach ($options as $option): 
                                    $isSelected = $question['selected_answer'] == $option['id'];
                                    $isCorrect = $option['is_correct'] == '1';
                                    $bgClass = $isSelected ? 
                                        ($isCorrect ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') : 
                                        ($isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200');
                                    $textClass = $isSelected ? 
                                        ($isCorrect ? 'text-green-800' : 'text-red-800') : 
                                        ($isCorrect ? 'text-green-800' : 'text-gray-800');
                                ?>
                                    <div class="flex items-center p-3 border rounded-lg <?php echo $bgClass; ?>">
                                        <div class="flex-1 <?php echo $textClass; ?>">
                                            <?php echo htmlspecialchars($option['text']); ?>
                                        </div>
                                        <?php if ($isSelected || $isCorrect): ?>
                                            <div class="ml-3">
                                                <?php if ($isCorrect): ?>
                                                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                <?php elseif ($isSelected): ?>
                                                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 text-sm text-gray-500">
                            Points: <?php echo $question['points']; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Back to Results Button -->
        <div class="mt-8 flex justify-center">
            <a href="view_results.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Results
            </a>
        </div>
    </div>
</body>
</html>
