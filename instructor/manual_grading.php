<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;

// Get exam details
$stmt = $pdo->prepare("
    SELECT e.*, COUNT(DISTINCT es.id) as submission_count
    FROM exams e
    LEFT JOIN exam_submissions es ON e.id = es.exam_id
    WHERE e.id = ? AND e.instructor_id = ?
    GROUP BY e.id
");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: manage_exams.php');
    exit;
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['grades'] as $submission_id => $answers) {
        foreach ($answers as $answer_id => $score) {
            $stmt = $pdo->prepare("UPDATE answer_submissions SET score = ? WHERE id = ?");
            $stmt->execute([$score, $answer_id]);
        }
    }

    // Update total scores
    $stmt = $pdo->prepare("
        UPDATE exam_submissions es
        SET total_score = (
            SELECT SUM(score)
            FROM answer_submissions
            WHERE submission_id = es.id
        ),
        status = 'graded'
        WHERE exam_id = ?
    ");
    $stmt->execute([$exam_id]);

    $_SESSION['message'] = 'Grades saved successfully!';
    header("Location: manual_grading.php?exam_id=$exam_id");
    exit;
}

// Get submissions that need manual grading
$stmt = $pdo->prepare("
    SELECT 
        es.id as submission_id,
        u.username as student_name,
        q.question_text,
        q.points as max_points,
        ans.id as answer_id,
        ans.answer_text,
        ans.score
    FROM exam_submissions es
    JOIN users u ON es.student_id = u.id
    JOIN answer_submissions ans ON es.id = ans.submission_id
    JOIN questions q ON ans.question_id = q.id
    WHERE es.exam_id = ?
    AND q.question_type = 'essay'
    ORDER BY es.submit_time DESC
");
$stmt->execute([$exam_id]);
$submissions = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Grading - <?php echo htmlspecialchars($exam['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manual Grading</h2>
                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($exam['title']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="autoSaveStatus" class="text-sm text-gray-500 hidden">
                        Saving...
                    </span>
                    <button type="button" 
                            onclick="document.getElementById('gradingForm').submit()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save All Grades
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grading Form -->
        <form id="gradingForm" method="POST" class="space-y-6">
            <?php 
            $current_submission = null;
            foreach ($submissions as $submission): 
                if ($current_submission != $submission['submission_id']):
                    if ($current_submission !== null) echo "</div>";
                    $current_submission = $submission['submission_id'];
            ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                <?php echo htmlspecialchars($submission['student_name']); ?>
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Submission #<?php echo $submission['submission_id']; ?>
                            </span>
                        </div>
                    </div>
            <?php endif; ?>

                <div class="p-6 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <!-- Question -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-900">Question:</h4>
                        <p class="mt-1 text-gray-600"><?php echo htmlspecialchars($submission['question_text']); ?></p>
                    </div>

                    <!-- Answer -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-900">Answer:</h4>
                        <div class="mt-1 p-4 bg-gray-50 rounded-md text-gray-600">
                            <?php echo nl2br(htmlspecialchars($submission['answer_text'])); ?>
                        </div>
                    </div>

                    <!-- Grading -->
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <span class="text-sm font-medium text-gray-700 mr-2">Score:</span>
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" 
                                       name="grades[<?php echo $submission['submission_id']; ?>][<?php echo $submission['answer_id']; ?>]" 
                                       value="<?php echo $submission['score']; ?>"
                                       min="0" 
                                       max="<?php echo $submission['max_points']; ?>"
                                       step="0.5"
                                       class="block w-20 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       onchange="validateScore(this, <?php echo $submission['max_points']; ?>)">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">/ <?php echo $submission['max_points']; ?></span>
                                </div>
                            </div>
                        </label>
                        
                        <!-- Optional: Add feedback textarea -->
                        <div class="flex-1">
                            <textarea name="feedback[<?php echo $submission['answer_id']; ?>]" 
                                      placeholder="Add feedback (optional)"
                                      class="block w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                      rows="1"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($current_submission !== null) echo "</div>"; ?>
        </form>

        <!-- Floating Save Button -->
        <div class="fixed bottom-6 right-6">
            <button type="button" 
                    onclick="document.getElementById('gradingForm').submit()"
                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-full shadow-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save All Changes
            </button>
        </div>
    </div>

    <script>
        function validateScore(input, maxPoints) {
            const value = parseFloat(input.value);
            if (value < 0) {
                input.value = 0;
            } else if (value > maxPoints) {
                input.value = maxPoints;
            }
            
            // Optional: Show visual feedback
            if (value === maxPoints) {
                input.classList.add('bg-green-50');
            } else if (value === 0) {
                input.classList.add('bg-red-50');
            } else {
                input.classList.remove('bg-green-50', 'bg-red-50');
            }
        }

        // Auto-save functionality
        let autoSaveTimeout;
        const formInputs = document.querySelectorAll('input[type="number"], textarea');
        const autoSaveStatus = document.getElementById('autoSaveStatus');

        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveStatus.classList.remove('hidden');
                
                autoSaveTimeout = setTimeout(() => {
                    // Add your auto-save logic here
                    console.log('Auto-saving...');
                    
                    // Simulate save completion
                    setTimeout(() => {
                        autoSaveStatus.classList.add('hidden');
                    }, 1000);
                }, 1000);
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
