<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$exam_id = $_GET['id'] ?? null;

// Get exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND instructor_id = ?");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: manage_exams.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    try {
        $stmt = $pdo->prepare("
            UPDATE exams 
            SET title = ?, description = ?, duration = ?, start_time = ?, end_time = ?
            WHERE id = ? AND instructor_id = ?
        ");
        $stmt->execute([
            $title, 
            $description, 
            $duration, 
            $start_time, 
            $end_time, 
            $exam_id, 
            $_SESSION['user_id']
        ]);

        $_SESSION['message'] = 'Exam updated successfully!';
        header('Location: manage_exams.php');
        exit;
    } catch(PDOException $e) {
        $error = "Error updating exam: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam - Exam Management System</title>
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
                <li class="flex items-center">
                    <a href="manage_exams.php" class="text-gray-500 hover:text-blue-600">Manage Exams</a>
                    <svg class="w-3 h-3 mx-3 text-gray-400" fill="currentColor" viewBox="0 0 320 512">
                        <path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                </li>
                <li class="text-gray-700">Edit Exam</li>
            </ol>
        </nav>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Edit Exam</h2>
                <p class="mt-1 text-sm text-gray-500">Update the exam details below</p>
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

            <!-- Form -->
            <form method="POST" class="p-6 space-y-6">
                <!-- Title -->
                <div class="space-y-1">
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($exam['title']); ?>" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- Description -->
                <div class="space-y-1">
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    ><?php echo htmlspecialchars($exam['description']); ?></textarea>
                    <p class="mt-2 text-sm text-gray-500">
                        Brief description of the exam content and objectives
                    </p>
                </div>

                <!-- Time Settings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Duration -->
                    <div class="space-y-1">
                        <label for="duration" class="block text-sm font-medium text-gray-700">
                            Duration (minutes) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="duration" 
                               name="duration" 
                               value="<?php echo $exam['duration']; ?>" 
                               required
                               min="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <!-- Start Time -->
                    <div class="space-y-1">
                        <label for="start_time" class="block text-sm font-medium text-gray-700">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="start_time" 
                               name="start_time" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($exam['start_time'])); ?>" 
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <!-- End Time -->
                    <div class="space-y-1">
                        <label for="end_time" class="block text-sm font-medium text-gray-700">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="end_time" 
                               name="end_time" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($exam['end_time'])); ?>" 
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Passing Score -->
                    <div class="space-y-1">
                        <label for="passing_score" class="block text-sm font-medium text-gray-700">
                            Passing Score (%)
                        </label>
                        <input type="number" 
                               id="passing_score" 
                               name="passing_score" 
                               value="<?php echo $exam['passing_score'] ?? 60; ?>"
                               min="0"
                               max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <!-- Attempts Allowed -->
                    <div class="space-y-1">
                        <label for="attempts" class="block text-sm font-medium text-gray-700">
                            Attempts Allowed
                        </label>
                        <input type="number" 
                               id="attempts" 
                               name="attempts" 
                               value="<?php echo $exam['attempts'] ?? 1; ?>"
                               min="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <a href="manage_exams.php" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Update Exam
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = new Date(document.getElementById('start_time').value);
            const endTime = new Date(document.getElementById('end_time').value);
            const duration = parseInt(document.getElementById('duration').value);

            if (endTime <= startTime) {
                e.preventDefault();
                alert('End time must be after start time');
                return;
            }

            const examDuration = (endTime - startTime) / (1000 * 60); // Convert to minutes
            if (duration > examDuration) {
                e.preventDefault();
                alert('Duration cannot be longer than the time between start and end time');
                return;
            }

            if (duration <= 0) {
                e.preventDefault();
                alert('Duration must be greater than 0');
                return;
            }
        });

        // Auto-save functionality (optional)
        let autoSaveTimeout;
        const formInputs = document.querySelectorAll('form input, form textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    // Add your auto-save logic here
                    console.log('Auto-saving...');
                }, 2000);
            });
        });
    </script>
</body>
</html>
