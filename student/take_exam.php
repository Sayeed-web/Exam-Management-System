<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

require '../db.php';

// Initialize variables
$error_message = '';
$exam_id = $_GET['exam_id'] ?? null;
$student_id = $_SESSION['user_id'];

try {
    // Validate exam ID
    if (!$exam_id || !is_numeric($exam_id)) {
        throw new Exception('Invalid exam ID');
    }

    // Get exam details with more information
    $stmt = $pdo->prepare("
        SELECT e.*, 
               CASE 
                   WHEN NOW() < e.start_time THEN 'not_started'
                   WHEN NOW() > e.end_time THEN 'ended'
                   ELSE 'in_progress'
               END as exam_status,
               (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as total_questions,
               i.name as instructor_name
        FROM exams e
        LEFT JOIN users i ON e.instructor_id = i.id
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        throw new Exception('Exam not found');
    }

    // Check exam status
    if ($exam['exam_status'] === 'not_started') {
        throw new Exception('This exam has not started yet');
    }
    if ($exam['exam_status'] === 'ended') {
        throw new Exception('This exam has ended');
    }

    // Check if student already submitted this exam
    $stmt = $pdo->prepare("
        SELECT * FROM exam_submissions 
        WHERE exam_id = ? AND student_id = ?
    ");
    $stmt->execute([$exam_id, $student_id]);
    $existing_submission = $stmt->fetch();

    if ($existing_submission && $existing_submission['status'] == 'submitted') {
        throw new Exception('You have already submitted this exam');
    }

    // Create or get submission
    if (!$existing_submission) {
        $stmt = $pdo->prepare("
            INSERT INTO exam_submissions (
                exam_id, 
                student_id, 
                start_time, 
                status,
                remaining_time
            ) VALUES (?, ?, NOW(), 'in_progress', ?)
        ");
        $stmt->execute([$exam_id, $student_id, $exam['duration'] * 60]);
        $submission_id = $pdo->lastInsertId();
    } else {
        $submission_id = $existing_submission['id'];
        
        // Calculate remaining time
        $start_time = strtotime($existing_submission['start_time']);
        $elapsed_time = time() - $start_time;
        $remaining_time = max(0, ($exam['duration'] * 60) - $elapsed_time);
        
        // Update remaining time
        $stmt = $pdo->prepare("
            UPDATE exam_submissions 
            SET remaining_time = ? 
            WHERE id = ?
        ");
        $stmt->execute([$remaining_time, $submission_id]);
    }

    // Get questions with randomization option
    $stmt = $pdo->prepare("
        SELECT 
            q.*, 
            GROUP_CONCAT(
                CONCAT(qo.id, ':', qo.option_text)
                ORDER BY " . ($exam['randomize_options'] ? 'RAND()' : 'qo.id') . "
                SEPARATOR '|'
            ) as options
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id
        " . ($exam['randomize_questions'] ? 'ORDER BY RAND()' : 'ORDER BY q.id')
    );
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll();

    // Get any saved answers
    $stmt = $pdo->prepare("
        SELECT question_id, answer_text, selected_option_id
        FROM exam_answers
        WHERE submission_id = ?
    ");
    $stmt->execute([$submission_id]);
    $saved_answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: view_exams.php');
    exit;
}

// Helper function to check if an answer exists
function get_saved_answer($question_id, $saved_answers) {
    return $saved_answers[$question_id] ?? null;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam - <?php echo htmlspecialchars($exam['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">



<!-- Add this at the top of the page, just after the opening body tag -->
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-sm font-medium">Back to Dashboard</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">Student: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen">
        <!-- Fixed Header -->
        <header class="bg-white shadow-sm fixed top-16 w-full z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <button onclick="confirmExit()" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($exam['title']); ?></h1>
                            <p class="text-sm text-gray-500">
                                Total Questions: <?php echo count($questions); ?> | 
                                Duration: <?php echo $exam['duration']; ?> minutes
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div id="timer" class="text-2xl font-bold text-blue-600"></div>
                        <p class="text-sm text-gray-500">Time Remaining</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content - Adjust top padding to account for both navbars -->
        <main class="pt-36 pb-20">
            <!-- Rest of your content remains the same -->
        </main>

        <!-- Fixed Footer -->
        <footer class="fixed bottom-0 w-full bg-white border-t shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                        <button onclick="confirmExit()" class="text-gray-600 hover:text-gray-900">
                            Exit Exam
                        </button>
                    </div>
                    <button onclick="confirmSubmit()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Submit Exam
                    </button>
                </div>
            </div>
        </footer>
    </div>

    <!-- Add this to your existing JavaScript -->
    <script>
        // Modify your existing confirmExit function
        function confirmExit() {
            if (confirm('Are you sure you want to exit? Your progress will be saved.')) {
                saveAnswers().then(() => {
                    window.location.href = 'dashboard.php';
                });
            }
        }

        // Modify your saveAnswers function to return a promise
        async function saveAnswers() {
            const formData = new FormData(document.getElementById('examForm'));
            try {
                await fetch('save_answers.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error saving answers:', error);
            }
        }

        // Add keyboard shortcut for dashboard
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + D for Dashboard
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                confirmExit();
            }
        });

        // Show warning modal for navigation
        const showNavigationWarning = (e) => {
            if (!e.target.closest('a[href="dashboard.php"]')) return;
            
            e.preventDefault();
            confirmExit();
        };

        document.addEventListener('click', showNavigationWarning);
    </script>

    <!-- Add this modal for unsaved changes -->
    <div id="unsavedChangesModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Unsaved Changes</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        You have unsaved changes. Would you like to save your progress before leaving?
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="saveAndLeave" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Save & Leave
                    </button>
                    <button id="leaveWithoutSaving" class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Leave without Saving
                    </button>
                    <button id="stayOnPage" class="mt-3 px-4 py-2 bg-white text-gray-600 text-base font-medium rounded-md border hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Stay on Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Add these styles for better navigation visibility */
        .nav-link {
            @apply flex items-center px-3 py-2 rounded-md text-sm font-medium;
        }
        
        .nav-link-active {
            @apply bg-blue-50 text-blue-700;
        }
        
        .nav-link-default {
            @apply text-gray-600 hover:bg-gray-50 hover:text-gray-900;
        }

        /* Add animation for the modal */
        .modal-enter {
            animation: modal-enter 0.3s ease-out;
        }

        @keyframes modal-enter {
            from {
                opacity: 0;
                transform: translateY(-60px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>




    <!-- Add the rest of your HTML here -->
    <div class="min-h-screen">
        <!-- Fixed Header -->
        <header class="bg-white shadow-sm fixed top-0 w-full z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <button onclick="confirmExit()" class="text-gray-500 hover:text-gray-700 mr-4">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($exam['title']); ?></h1>
                            <p class="text-sm text-gray-500">
                                Total Questions: <?php echo count($questions); ?> | 
                                Duration: <?php echo $exam['duration']; ?> minutes
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div id="timer" class="text-2xl font-bold text-blue-600"></div>
                        <p class="text-sm text-gray-500">Time Remaining</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="pt-24 pb-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <form id="examForm" method="POST" action="submit_exam.php" class="space-y-6">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                    
                    <!-- Questions -->
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Question <?php echo $index + 1; ?> of <?php echo count($questions); ?>
                                </h3>
                                <span class="text-sm text-gray-500">
                                    <?php echo ucfirst($question['question_type']); ?> Question
                                </span>
                            </div>
                            
                            <p class="text-gray-800 mb-4"><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <div class="space-y-3">
                                <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                    <?php
                                    $options = array_map(function($opt) {
                                        list($id, $text) = explode(':', $opt);
                                        return ['id' => $id, 'text' => $text];
                                    }, explode('|', $question['options']));
                                    ?>
                                    <?php foreach ($options as $option): ?>
                                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                            <input type="radio" 
                                                   name="answers[<?php echo $question['id']; ?>]" 
                                                   value="<?php echo $option['id']; ?>"
                                                   <?php echo (get_saved_answer($question['id'], $saved_answers) == $option['id']) ? 'checked' : ''; ?>
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                                                   required>
                                            <span class="ml-3"><?php echo htmlspecialchars($option['text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        </main>

        <!-- Fixed Footer -->
        <footer class="fixed bottom-0 w-full bg-white border-t shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <button onclick="confirmExit()" class="text-gray-600 hover:text-gray-900">
                        Exit Exam
                    </button>
                    <button onclick="confirmSubmit()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Submit Exam
                    </button>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Initialize timer
        let timeLeft = <?php echo $existing_submission['remaining_time'] ?? ($exam['duration'] * 60); ?>;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 300) { // 5 minutes warning
                document.getElementById('timer').classList.add('text-red-600');
            }
            
            if (timeLeft <= 0) {
                submitExam();
            }
            
            timeLeft--;
        }

        // Update timer every second
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);

        // Auto-save answers every 30 seconds
        setInterval(saveAnswers, 30000);

        function saveAnswers() {
            const formData = new FormData(document.getElementById('examForm'));
            fetch('save_answers.php', {
                method: 'POST',
                body: formData
            });
        }

        function confirmExit() {
            if (confirm('Are you sure you want to exit? Your progress will be saved.')) {
                window.location.href = 'view_exams.php';
            }
        }

        function confirmSubmit() {
            if (confirm('Are you sure you want to submit your exam? This cannot be undone.')) {
                submitExam();
            }
        }

        function submitExam() {
            clearInterval(timerInterval);
            document.getElementById('examForm').submit();
        }

        // Prevent accidental navigation
        window.onbeforeunload = function() {
            return "Are you sure you want to leave? Your progress will be saved.";
        };

        // Remove warning when submitting
        document.getElementById('examForm').onsubmit = function() {
            window.onbeforeunload = null;
        };
    </script>
</body>
</html>
