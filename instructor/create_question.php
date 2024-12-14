<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;

// Verify exam belongs to instructor
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND instructor_id = ?");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: manage_exams.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];
    $points = $_POST['points'];
    
    $pdo->beginTransaction();
    try {
        // Insert question
        $stmt = $pdo->prepare("
            INSERT INTO questions (exam_id, question_text, question_type, points, correct_answer)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        // Handle correct answer based on question type
        $correct_answer = null;
        if ($question_type == 'true_false') {
            $correct_answer = $_POST['correct_tf'];
        } elseif ($question_type == 'essay') {
            $correct_answer = $_POST['model_answer'];
        }
        
        $stmt->execute([$exam_id, $question_text, $question_type, $points, $correct_answer]);
        $question_id = $pdo->lastInsertId();

        // Handle options for multiple choice questions
        if ($question_type == 'multiple_choice') {
            foreach ($_POST['options'] as $index => $option_text) {
                $is_correct = isset($_POST['correct_option']) && $_POST['correct_option'] == $index;
                $stmt = $pdo->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }
        }

        $pdo->commit();
        $_SESSION['message'] = 'Question added successfully!';
        header("Location: edit_exam.php?id=$exam_id");
        exit;
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error creating question: " . $e->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Question - Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Inter font for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Create New Question</h1>
            <p class="text-gray-600">Add a new question to your exam bank</p>
        </div>
        
        <form id="questionForm" class="bg-white shadow-lg rounded-xl p-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center space-x-2 mb-2">
                    <div class="h-2 w-1/3 rounded-full bg-blue-500"></div>
                    <div class="h-2 w-1/3 rounded-full bg-gray-200"></div>
                    <div class="h-2 w-1/3 rounded-full bg-gray-200"></div>
                </div>
                <p class="text-sm text-gray-500">Step 1 of 3: Question Details</p>
            </div>

            <!-- Question Type Selection -->
            <div class="mb-8">
                <label for="questionType" class="block text-gray-700 font-semibold mb-2 text-lg">
                    Question Type
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <input type="radio" id="multiple_choice" name="question_type" value="multiple_choice" 
                               class="peer hidden" checked>
                        <label for="multiple_choice" 
                               class="block p-4 border-2 rounded-lg cursor-pointer transition-all
                                      peer-checked:border-blue-500 peer-checked:bg-blue-50
                                      hover:bg-gray-50">
                            <div class="font-semibold mb-1">Multiple Choice</div>
                            <div class="text-sm text-gray-500">Create questions with multiple options</div>
                        </label>
                    </div>
                    <div class="relative">
                        <input type="radio" id="true_false" name="question_type" value="true_false" 
                               class="peer hidden">
                        <label for="true_false" 
                               class="block p-4 border-2 rounded-lg cursor-pointer transition-all
                                      peer-checked:border-blue-500 peer-checked:bg-blue-50
                                      hover:bg-gray-50">
                            <div class="font-semibold mb-1">True/False</div>
                            <div class="text-sm text-gray-500">Simple binary choice questions</div>
                        </label>
                    </div>
                    <div class="relative">
                        <input type="radio" id="essay" name="question_type" value="essay" 
                               class="peer hidden">
                        <label for="essay" 
                               class="block p-4 border-2 rounded-lg cursor-pointer transition-all
                                      peer-checked:border-blue-500 peer-checked:bg-blue-50
                                      hover:bg-gray-50">
                            <div class="font-semibold mb-1">Essay</div>
                            <div class="text-sm text-gray-500">Long-form written responses</div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Question Text -->
            <div class="mb-8">
                <label for="questionText" class="block text-gray-700 font-semibold mb-2 text-lg">
                    Question Text
                </label>
                <div class="relative">
                    <textarea id="questionText" name="question_text" rows="4" required
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500
                               transition-colors text-gray-800 resize-none"
                        placeholder="Enter your question here..."></textarea>
                    <div class="absolute bottom-3 right-3 text-sm text-gray-400">
                        <span id="charCount">0</span>/500
                    </div>
                </div>
            </div>

            <!-- Points and Difficulty -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="points" class="block text-gray-700 font-semibold mb-2 text-lg">
                        Points
                    </label>
                    <div class="relative">
                        <input type="number" id="points" name="points" min="1" max="100" required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500
                                   transition-colors text-gray-800">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500">pts</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="difficulty" class="block text-gray-700 font-semibold mb-2 text-lg">
                        Difficulty Level
                    </label>
                    <select id="difficulty" name="difficulty" 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500
                                   transition-colors text-gray-800">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Options Section -->
            <div id="optionsContainer" class="mb-8">
                <!-- Multiple Choice Options -->
                <div id="multipleChoiceOptions" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-700">Answer Options</h3>
                        <button type="button" onclick="addOption()" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 
                                       transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Add Option
                        </button>
                    </div>
                    <div id="optionsList" class="space-y-3">
                        <!-- Options will be added here dynamically -->
                    </div>
                </div>

                <!-- True/False Options -->
                <div id="trueFalseOptions" class="hidden">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Correct Answer</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <input type="radio" id="true" name="correct_tf" value="true" 
                                   class="peer hidden">
                            <label for="true" 
                                   class="block p-4 border-2 rounded-lg cursor-pointer text-center transition-all
                                          peer-checked:border-green-500 peer-checked:bg-green-50
                                          hover:bg-gray-50">
                                True
                            </label>
                        </div>
                        <div class="relative">
                            <input type="radio" id="false" name="correct_tf" value="false" 
                                   class="peer hidden">
                            <label for="false" 
                                   class="block p-4 border-2 rounded-lg cursor-pointer text-center transition-all
                                          peer-checked:border-red-500 peer-checked:bg-red-50
                                          hover:bg-gray-50">
                                False
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Essay Options -->
                <div id="essayOptions" class="hidden space-y-6">
                    <div>
                        <label for="wordLimit" class="block text-gray-700 font-semibold mb-2 text-lg">
                            Word Limit
                        </label>
                        <div class="relative">
                            <input type="number" id="wordLimit" name="word_limit" min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500
                                          transition-colors text-gray-800">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">words</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="sampleAnswer" class="block text-gray-700 font-semibold mb-2 text-lg">
                            Sample Answer
                        </label>
                        <textarea id="sampleAnswer" name="sample_answer" rows="4"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500
                                       transition-colors text-gray-800 resize-none"
                                placeholder="Enter a sample answer..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4">
                <button type="button" 
                        class="px-6 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50
                               transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Save as Draft
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600
                               transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Create Question
                </button>
            </div>
        </form>
    </div>

    <script>
        // Your existing JavaScript code here
        // Add this new function for character counting
        document.getElementById('questionText').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
            if (charCount > 500) {
                this.value = this.value.substring(0, 500);
            }
        });

        function addOption() {
            const optionsList = document.getElementById('optionsList');
            const optionCount = optionsList.children.length;
            
            if (optionCount >= 6) {
                alert('Maximum 6 options allowed');
                return;
            }

            const optionRow = document.createElement('div');
            optionRow.className = 'flex items-center space-x-4 p-4 border-2 border-gray-200 rounded-lg';
            optionRow.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="options[]" placeholder="Option ${optionCount + 1}" required
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-md focus:outline-none focus:border-blue-500
                                  transition-colors">
                </div>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="correct_option" value="${optionCount}" required
                               class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="ml-2 text-gray-700">Correct</span>
                    </label>
                    <button type="button" onclick="removeOption(this)"
                            class="p-2 text-red-500 hover:bg-red-50 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `;
            
            optionsList.appendChild(optionRow);
        }

        // Initialize with default options
        window.addEventListener('DOMContentLoaded', () => {
            addOption();
            addOption();
            
            // Add event listeners for question type selection
            document.querySelectorAll('input[name="question_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    toggleQuestionTypes(this.value);
                });
            });
        });

        function toggleQuestionTypes(type) {
            const multipleChoiceOptions = document.getElementById('multipleChoiceOptions');
            const trueFalseOptions = document.getElementById('trueFalseOptions');
            const essayOptions = document.getElementById('essayOptions');

            [multipleChoiceOptions, trueFalseOptions, essayOptions].forEach(el => {
                el.classList.add('hidden');
            });

            switch(type) {
                case 'multiple_choice':
                    multipleChoiceOptions.classList.remove('hidden');
                    break;
                case 'true_false':
                    trueFalseOptions.classList.remove('hidden');
                    break;
                case 'essay':
                    essayOptions.classList.remove('hidden');
                    break;
            }
        }

        // Your existing removeOption and form validation code here
    </script>
</body>
</html>
