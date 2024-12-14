<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$question_id = $_GET['id'] ?? null;
if (!$question_id) {
    header('Location: manage_exams.php');
    exit;
}

// Get question details
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    header('Location: manage_exams.php');
    exit;
}

// Get options if multiple choice
$options = [];
if ($question['question_type'] == 'multiple_choice') {
    $stmt = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $points = $_POST['points'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, points = ? WHERE id = ?");
        $stmt->execute([$question_text, $points, $question_id]);

        if ($question['question_type'] == 'multiple_choice') {
            // Delete existing options
            $stmt = $pdo->prepare("DELETE FROM question_options WHERE question_id = ?");
            $stmt->execute([$question_id]);

            // Add new options
            foreach ($_POST['options'] as $index => $option_text) {
                $is_correct = isset($_POST['correct_option']) && $_POST['correct_option'] == $index;
                $stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) 
                                     VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }
        }

        $pdo->commit();
        $_SESSION['message'] = 'Question updated successfully!';
        header('Location: manage_exams.php');
        exit;
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error updating question: " . $e->getMessage();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="dashboard.php" class="text-gray-500 hover:text-blue-600">Dashboard</a>
                    <svg class="w-3 h-3 mx-3 text-gray-400" fill="currentColor" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li class="text-gray-700">Edit Question</li>
            </ol>
        </nav>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Edit Question</h2>
                <p class="mt-1 text-sm text-gray-500">Update the question details and options below</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="p-4 mx-6 mt-6 bg-red-50 border-l-4 border-red-500">
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

            <!-- Question Form -->
            <form method="POST" class="p-6 space-y-6">
                <!-- Question Text -->
                <div class="space-y-1">
                    <label for="question_text" class="block text-sm font-medium text-gray-700">
                        Question Text <span class="text-red-500">*</span>
                    </label>
                    <textarea id="question_text" 
                              name="question_text" 
                              rows="4" 
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    ><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                </div>

                <!-- Points -->
                <div class="space-y-1">
                    <label for="points" class="block text-sm font-medium text-gray-700">
                        Points <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="points" 
                           name="points" 
                           value="<?php echo $question['points']; ?>" 
                           required
                           min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- Multiple Choice Options -->
                <?php if ($question['question_type'] == 'multiple_choice'): ?>
                    <div id="options_div" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Answer Options</h3>
                            <button type="button" 
                                    onclick="addOption()"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Option
                            </button>
                        </div>

                        <div id="options_container" class="space-y-3">
                            <?php foreach ($options as $index => $option): ?>
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg group hover:bg-gray-100 transition-colors">
                                    <div class="flex-1">
                                        <input type="text" 
                                               name="options[]" 
                                               value="<?php echo htmlspecialchars($option['option_text']); ?>" 
                                               required
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                               placeholder="Enter option text">
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" 
                                                   name="correct_option" 
                                                   value="<?php echo $index; ?>" 
                                                   <?php echo $option['is_correct'] ? 'checked' : ''; ?> 
                                                   required
                                                   class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                            <span class="ml-2 text-sm text-gray-700">Correct</span>
                                        </label>
                                        <button type="button" 
                                                onclick="removeOption(this)"
                                                class="text-gray-400 hover:text-red-500 focus:outline-none">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <a href="manage_questions.php" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Update Question
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addOption() {
            const optionsContainer = document.getElementById('options_container');
            const optionCount = optionsContainer.children.length;
            
            // Limit maximum options
            if (optionCount >= 6) {
                alert('Maximum 6 options allowed');
                return;
            }

            const newOption = document.createElement('div');
            newOption.className = 'flex items-center space-x-4 p-4 bg-gray-50 rounded-lg group hover:bg-gray-100 transition-colors';
            newOption.innerHTML = `
                <div class="flex-1">
                    <input type="text" 
                           name="options[]" 
                           required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Enter option text">
                </div>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" 
                               name="correct_option" 
                               value="${optionCount}" 
                               required
                               class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                        <span class="ml-2 text-sm text-gray-700">Correct</span>
                    </label>
                    <button type="button" 
                            onclick="removeOption(this)"
                            class="text-gray-400 hover:text-red-500 focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            `;
            
            optionsContainer.appendChild(newOption);
            updateOptionIndexes();
        }

        function removeOption(button) {
            const optionsContainer = document.getElementById('options_container');
            if (optionsContainer.children.length <= 2) {
                alert('Minimum 2 options required');
                return;
            }
            
            button.closest('div.flex.items-center').remove();
            updateOptionIndexes();
        }

        function updateOptionIndexes() {
            const options = document.querySelectorAll('input[name="correct_option"]');
            options.forEach((option, index) => {
                option.value = index;
            });
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const options = document.getElementsByName('options[]');
            const correctOption = document.querySelector('input[name="correct_option"]:checked');
            
            if (options.length < 2) {
                e.preventDefault();
                alert('At least 2 options are required');
                return;
            }

            if (!correctOption) {
                e.preventDefault();
                alert('Please select a correct answer');
                return;
            }

            // Check for duplicate options
            const optionTexts = new Set();
            for (let option of options) {
                const text = option.value.trim();
                if (optionTexts.has(text)) {
                    e.preventDefault();
                    alert('Duplicate options are not allowed');
                    return;
                }
                optionTexts.add(text);
            }
        });
    </script>
</body>
</html>
