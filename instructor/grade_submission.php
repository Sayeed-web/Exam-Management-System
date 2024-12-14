<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$submission_id = isset($_GET['id']) ? $_GET['id'] : null;

// Verify submission exists and belongs to an exam created by this instructor
$stmt = $pdo->prepare("
    SELECT 
        es.*,
        e.title as exam_title,
        u.username as student_name,
        e.instructor_id
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON es.student_id = u.id
    WHERE es.id = ? AND e.instructor_id = ?
");
$stmt->execute(array($submission_id, $_SESSION['user_id']));
$submission = $stmt->fetch();

if (!$submission) {
    header('Location: manage_exams.php');
    exit;
}

// Handle form submission for grading
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Update individual answer scores
        foreach ($_POST['scores'] as $answer_id => $score) {
            $stmt = $pdo->prepare("
                UPDATE answer_submissions 
                SET score = ?, 
                    feedback = ?
                WHERE id = ?
            ");
            $stmt->execute(array(
                $score,
                $_POST['feedback'][$answer_id],
                $answer_id
            ));
        }

        // Calculate and update total score
        $stmt = $pdo->prepare("
            UPDATE exam_submissions 
            SET total_score = (
                SELECT SUM(score) 
                FROM answer_submissions 
                WHERE submission_id = ?
            ),
            status = 'graded'
            WHERE id = ?
        ");
        $stmt->execute(array($submission_id, $submission_id));

        $pdo->commit();
        $_SESSION['message'] = 'Submission graded successfully!';
        header("Location: view_results.php?exam_id=" . $submission['exam_id']);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error grading submission: " . $e->getMessage();
    }
}

// Get all answers for this submission
$stmt = $pdo->prepare("
    SELECT 
        a.id as answer_id,
        a.answer_text,
        a.selected_option_id,
        a.score,
        a.feedback,
        q.question_text,
        q.question_type,
        q.points as max_points,
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
$stmt->execute(array($submission_id));
$answers = $stmt->fetchAll();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Grade Submission</h2>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Exam:</span>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($submission['exam_title']); ?></p>
                    </div>
                    <div>
                        <span class="text-gray-500">Student:</span>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($submission['student_name']); ?></p>
                    </div>
                    <div>
                        <span class="text-gray-500">Submitted:</span>
                        <p class="font-medium text-gray-900"><?php echo date('Y-m-d H:i', strtotime($submission['submit_time'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grading Form -->
        <form method="POST" id="gradingForm" class="space-y-6">
            <?php foreach ($answers as $answer): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Question -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($answer['question_text']); ?>
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Maximum points: <?php echo $answer['max_points']; ?>
                                </p>
                            </div>
                            <?php if ($answer['question_type'] == 'multiple_choice'): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Multiple Choice
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    Essay
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Answer -->
                    <div class="px-6 py-4 bg-gray-50">
                        <?php if ($answer['question_type'] == 'multiple_choice'): ?>
                            <?php
                            $options = array();
                            foreach (explode('|', $answer['options']) as $option) {
                                list($id, $text, $is_correct) = explode(':', $option);
                                $options[$id] = array(
                                    'text' => $text,
                                    'is_correct' => $is_correct
                                );
                            }
                            ?>
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-500">Selected answer:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php 
                                        if (isset($options[$answer['selected_option_id']])) {
                                            echo htmlspecialchars($options[$answer['selected_option_id']]['text']);
                                            if ($options[$answer['selected_option_id']]['is_correct']) {
                                                echo ' <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Correct</span>';
                                            } else {
                                                echo ' <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Incorrect</span>';
                                            }
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-500">Correct answer:</span>
                                    <span class="text-sm text-gray-900">
                                        <?php
                                        foreach ($options as $id => $option) {
                                            if ($option['is_correct']) {
                                                echo htmlspecialchars($option['text']);
                                            }
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="prose prose-sm max-w-none">
                                <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grading Section -->
                    <div class="p-6 bg-white border-t border-gray-200 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Score</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" 
                                           name="scores[<?php echo $answer['answer_id']; ?>]"
                                           value="<?php echo $answer['score']; ?>"
                                           min="0" 
                                           max="<?php echo $answer['max_points']; ?>" 
                                           step="0.5" 
                                           required
                                           class="block w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           onchange="validateScore(this, <?php echo $answer['max_points']; ?>)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">/ <?php echo $answer['max_points']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Feedback</label>
                                <textarea name="feedback[<?php echo $answer['answer_id']; ?>]"
                                          rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                          placeholder="Provide feedback to the student..."
                                ><?php echo htmlspecialchars($answer['feedback']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="view_results.php?exam_id=<?php echo $submission['exam_id']; ?>" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Back to Results
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Grades
                </button>
            </div>
        </form>
    </div>

    <script>
        function validateScore(input, maxPoints) {
            const value = parseFloat(input.value);
            if (value < 0) {
                input.value = 0;
            } else if (value > maxPoints) {
                input.value = maxPoints;
            }
        }

        // Auto-save functionality
        let autoSaveTimeout;
        const formInputs = document.querySelectorAll('input, textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    // Add your auto-save logic here
                    console.log('Auto-saving...');
                }, 2000);
            });
        });

        // Form validation
        document.getElementById('gradingForm').addEventListener('submit', function(e) {
            const scores = document.querySelectorAll('input[type="number"]');
            let isValid = true;

            scores.forEach(score => {
                const value = parseFloat(score.value);
                const max = parseFloat(score.getAttribute('max'));
                
                if (value < 0 || value > max || isNaN(value)) {
                    isValid = false;
                    score.classList.add('border-red-500');
                } else {
                    score.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please ensure all scores are valid and within the maximum points range.');
            }
        });
    </script>
</body>
</html>
