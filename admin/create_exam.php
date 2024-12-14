<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get all instructors for assignment
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'instructor'");
$stmt->execute();
$instructors = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $instructor_id = $_POST['instructor_id'];
    $duration = $_POST['duration'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $passing_score = $_POST['passing_score'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO exams (
                title, description, instructor_id, duration, 
                start_time, end_time, passing_score
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $description, $instructor_id, $duration,
            $start_time, $end_time, $passing_score
        ]);

        $_SESSION['message'] = 'Exam created successfully!';
        header('Location: manage_exams.php');
        exit;
    } catch(PDOException $e) {
        $error = "Error creating exam: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam | Exam Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add FontAwesome for beautiful icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom animations */
        .animate-fade-in { animation: fadeIn 0.5s ease-in; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200 fixed w-full z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-primary-600 text-2xl mr-3"></i>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-primary-600 to-primary-700 bg-clip-text text-transparent">
                        Exam Management System
                    </h1>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-16">
        <!-- Breadcrumb -->
        <div class="bg-white/80 backdrop-blur-md border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-3">
                    <div class="flex items-center space-x-2 text-sm">
                        <a href="dashboard.php" class="text-primary-600 hover:text-primary-700">
                            <i class="fas fa-home"></i>
                            <span class="ml-1">Dashboard</span>
                        </a>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        <span class="text-gray-600">Create Exam</span>
                    </div>
                </div>
            </div>
        </div>

        <main class="py-8 animate-fade-in">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="text-center mb-8 animate-slide-in">
                    <h2 class="text-3xl font-bold text-gray-900">Create New Exam</h2>
                    <p class="mt-2 text-sm text-gray-600">Design your perfect examination with our intuitive form builder</p>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8 animate-slide-in">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <span class="text-xs mt-2 text-primary-600 font-medium">Basic Info</span>
                        </div>
                        <div class="flex-1 h-1 bg-primary-100 mx-4"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="text-xs mt-2 text-gray-500">Timing</span>
                        </div>
                        <div class="flex-1 h-1 bg-gray-200 mx-4"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="text-xs mt-2 text-gray-500">Scoring</span>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <?php if (isset($error)): ?>
                <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                        <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Form -->
                <form method="POST" class="space-y-6">
                                   <!-- Basic Details Card -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all hover:shadow-xl animate-slide-in">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-book text-primary-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Basic Details</h3>
                                    <p class="text-sm text-gray-500">Enter the fundamental information about your exam</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 space-y-6 bg-white/50 backdrop-blur-md">
                            <!-- Title -->
                            <div class="group relative">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-heading text-gray-400 mr-1"></i> Exam Title
                                </label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       required 
                                       class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md"
                                       placeholder="e.g., Final Mathematics Examination 2024">
                            </div>

                            <!-- Description -->
                            <div class="group relative">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-align-left text-gray-400 mr-1"></i> Description
                                </label>
                                <textarea id="description" 
                                          name="description" 
                                          rows="4" 
                                          class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md"
                                          placeholder="Provide a detailed description of the exam content, objectives, and special instructions..."></textarea>
                            </div>

                            <!-- Instructor Selection -->
                            <div class="group relative">
                                <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-user-tie text-gray-400 mr-1"></i> Assign Instructor
                                </label>
                                <div class="relative">
                                    <select id="instructor_id" 
                                            name="instructor_id" 
                                            required 
                                            class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md appearance-none">
                                        <option value="">Select an instructor</option>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <option value="<?php echo htmlspecialchars($instructor['id']); ?>">
                                                <?php echo htmlspecialchars($instructor['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Time Settings Card -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all hover:shadow-xl animate-slide-in">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-primary-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Time Settings</h3>
                                    <p class="text-sm text-gray-500">Configure the timing and duration of your exam</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 space-y-6 bg-white/50 backdrop-blur-md">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Start Time -->
                                <div class="group relative">
                                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-play text-gray-400 mr-1"></i> Start Time
                                    </label>
                                    <input type="datetime-local" 
                                           id="start_time" 
                                           name="start_time" 
                                           required 
                                           class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md">
                                </div>

                                <!-- End Time -->
                                <div class="group relative">
                                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-stop text-gray-400 mr-1"></i> End Time
                                    </label>
                                    <input type="datetime-local" 
                                           id="end_time" 
                                           name="end_time" 
                                           required 
                                           class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md">
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="group relative">
                                <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-hourglass-half text-gray-400 mr-1"></i> Duration
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           id="duration" 
                                           name="duration" 
                                           required 
                                           min="1"
                                           class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md"
                                           placeholder="Enter exam duration">
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4">
                                        <span class="text-gray-500">minutes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scoring Settings Card -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all hover:shadow-xl animate-slide-in">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-primary-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Scoring Settings</h3>
                                    <p class="text-sm text-gray-500">Define the passing criteria for your exam</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-white/50 backdrop-blur-md">
                            <!-- Passing Score -->
                            <div class="group relative">
                                <label for="passing_score" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-percentage text-gray-400 mr-1"></i> Passing Score
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           id="passing_score" 
                                           name="passing_score" 
                                           required 
                                           min="0" 
                                           max="100"
                                           class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-600 focus:ring focus:ring-primary-100 transition-all duration-200 bg-white/50 backdrop-blur-md"
                                           placeholder="Enter passing score">
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4">
                                        <span class="text-gray-500">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <a href="manage_exams.php" 
                           class="px-6 py-3 rounded-lg border-2 border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                            <i class="fas fa-times mr-2"></i>
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-600">
                            <i class="fas fa-save mr-2"></i>
                            Create Exam
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Enhanced client-side validation with beautiful notifications
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = new Date(document.querySelector('input[name="start_time"]').value);
            const endTime = new Date(document.querySelector('input[name="end_time"]').value);
            
            if (endTime <= startTime) {
                e.preventDefault();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'fixed top-4 right-4 max-w-sm bg-white rounded-lg shadow-lg border-l-4 border-red-500 animate-fade-in';
                errorDiv.innerHTML = `
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Validation Error</p>
                                <p class="mt-1 text-sm text-gray-500">End time must be after start time</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(errorDiv);
                
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>
