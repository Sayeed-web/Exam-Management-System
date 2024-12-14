<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("INSERT INTO exams (title, description, duration, instructor_id, start_time, end_time) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $duration, $_SESSION['user_id'], $start_time, $end_time]);
    
    $_SESSION['message'] = 'Exam created successfully!';
    header('Location: manage_exams.php');
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam | Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Heroicons (optional for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <!-- You can add your logo here -->
                        <span class="text-xl font-bold text-blue-600">ExamSystem</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" 
                           class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1">
                            <i class="fas fa-home mr-2"></i> Dashboard
                        </a>
                        <a href="manage_exams.php" 
                           class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1">
                            <i class="fas fa-book mr-2"></i> Manage Exams
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb Navigation -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="manage_exams.php" class="text-gray-700 hover:text-blue-600">
                            Manage Exams
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-gray-500">Create Exam</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
            <!-- Header Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Create New Exam</h2>
                <p class="mt-2 text-gray-600">Fill in the details below to create a new exam.</p>
            </div>
            
            <form method="POST" class="space-y-6">
                <!-- Title Field -->
                <div class="space-y-2">
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Exam Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           required 
                           placeholder="Enter exam title"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                  p-2.5 border hover:border-gray-400 transition duration-150">
                </div>

                <!-- Description Field -->
                <div class="space-y-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="4" 
                              placeholder="Enter exam description"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                     p-2.5 border hover:border-gray-400 transition duration-150"></textarea>
                    <p class="text-sm text-gray-500">Provide a clear description of the exam for students.</p>
                </div>

                <!-- Duration and Points Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Duration Field -->
                    <div class="space-y-2">
                        <label for="duration" class="block text-sm font-medium text-gray-700">
                            Duration (minutes) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   name="duration" 
                                   id="duration" 
                                   required 
                                   min="1"
                                   placeholder="Enter duration"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                          p-2.5 border hover:border-gray-400 transition duration-150">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">min</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Points Field (Optional) -->
                    <div class="space-y-2">
                        <label for="total_points" class="block text-sm font-medium text-gray-700">
                            Total Points
                        </label>
                        <input type="number" 
                               name="total_points" 
                               id="total_points" 
                               min="0"
                               placeholder="Enter total points"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                      p-2.5 border hover:border-gray-400 transition duration-150">
                    </div>
                </div>

                <!-- Time Fields Container -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Start Time Field -->
                    <div class="space-y-2">
                        <label for="start_time" class="block text-sm font-medium text-gray-700">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="start_time" 
                               id="start_time" 
                               required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                      p-2.5 border hover:border-gray-400 transition duration-150">
                    </div>

                    <!-- End Time Field -->
                    <div class="space-y-2">
                        <label for="end_time" class="block text-sm font-medium text-gray-700">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               name="end_time" 
                               id="end_time" 
                               required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm
                                      p-2.5 border hover:border-gray-400 transition duration-150">
                    </div>
                </div>

                <!-- Additional Settings (Optional) -->
                <div class="space-y-4 border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900">Additional Settings</h3>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="shuffle_questions" 
                               name="shuffle_questions"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="shuffle_questions" class="ml-2 block text-sm text-gray-900">
                            Shuffle questions for each student
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="show_results" 
                               name="show_results"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="show_results" class="ml-2 block text-sm text-gray-900">
                            Show results immediately after submission
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <button type="button" 
                            onclick="window.location.href='manage_exams.php'" 
                            class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Exams
                    </button>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-plus mr-2"></i> Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-inner mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © <?php echo date('Y'); ?> Exam Management System. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = new Date(document.getElementById('start_time').value);
            const endTime = new Date(document.getElementById('end_time').value);
            const duration = document.getElementById('duration').value;
            
            if (endTime <= startTime) {
                e.preventDefault();
                alert('End time must be after start time');
                return;
            }

            // Calculate duration between start and end time in minutes
            const timeDiff = (endTime - startTime) / (1000 * 60);
            if (timeDiff < duration) {
                e.preventDefault();
                alert('The time window must be at least as long as the exam duration');
                return;
            }
        });

        // Dynamic time validation
        function validateTimes() {
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            
            if (startTime.value && endTime.value) {
                if (new Date(endTime.value) <= new Date(startTime.value)) {
                    endTime.setCustomValidity('End time must be after start time');
                } else {
                    endTime.setCustomValidity('');
                }
            }
        }

        document.getElementById('start_time').addEventListener('change', validateTimes);
        document.getElementById('end_time').addEventListener('change', validateTimes);
    </script>
</body>
</html>
