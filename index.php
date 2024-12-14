<?php
session_start();
require 'db.php';

// Get statistics
$stats = [
    'total_exams' => $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
    'total_students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'total_instructors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn(),
    'completed_exams' => $pdo->query("SELECT COUNT(*) FROM exam_submissions WHERE status = 'graded'")->fetchColumn()
];

// Get featured exams
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.title, 
        e.description,
        e.start_time, 
        e.duration,
        u.username as instructor_name,
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
        (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.id) as attempt_count
    FROM exams e
    JOIN users u ON e.instructor_id = u.id
    WHERE e.start_time > NOW() 
    ORDER BY e.start_time ASC 
    LIMIT 6
");
$stmt->execute();
$featured_exams = $stmt->fetchAll();

// Get testimonials
$testimonials = [
    [
        'name' => 'John Doe',
        'role' => 'Student',
        'image' => 'student1.jpg',
        'content' => 'This exam system has helped me improve my test-taking skills significantly.'
    ],
    [
        'name' => 'Jane Smith',
        'role' => 'Instructor',
        'image' => 'instructor1.jpg',
        'content' => 'The platform makes it easy to create and manage exams effectively.'
    ],
    // Add more testimonials as needed
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Advanced Online Assessment Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://unpkg.com/alpinejs" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            dark: '#0A1A2F',    // Darker blue
                            medium: '#1E3A5F',  // Medium blue
                            light: '#2E4A7F',   // Lighter blue
                            accent: '#3B82F6'    // Bright blue accent
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #3B82F6, #60A5FA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #0A1A2F 0%, #1E3A5F 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-white font-sans">
    <!-- Navigation -->
    <nav class="bg-primary-dark/95 backdrop-blur-lg fixed w-full z-50 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">ExamMaster</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-300 hover:text-white transition-colors duration-200">
                        Features
                    </a>
                    <a href="#exams" class="text-gray-300 hover:text-white transition-colors duration-200">
                        Exams
                    </a>
                    <a href="#testimonials" class="text-gray-300 hover:text-white transition-colors duration-200">
                        Testimonials
                    </a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" 
                           class="text-gray-300 hover:text-white transition-colors duration-200">
                            Dashboard
                        </a>
                        <a href="logout.php" 
                           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" 
                           class="text-white font-medium hover:text-blue-400 transition-colors duration-200">
                            Login
                        </a>
                        <a href="register.php" 
                           class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button x-data="{ isOpen: false }" 
                            @click="isOpen = !isOpen" 
                            class="text-gray-300 hover:text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-data="{ isMobileMenuOpen: false }" 
             x-show="isMobileMenuOpen" 
             @click.away="isMobileMenuOpen = false"
             class="md:hidden bg-primary-dark border-t border-white/10">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#features" class="block px-3 py-2 text-gray-300 hover:text-white">Features</a>
                <a href="#exams" class="block px-3 py-2 text-gray-300 hover:text-white">Exams</a>
                <a href="#testimonials" class="block px-3 py-2 text-gray-300 hover:text-white">Testimonials</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="block px-3 py-2 text-gray-300 hover:text-white">Dashboard</a>
                    <a href="logout.php" class="block px-3 py-2 text-red-500 hover:text-red-400">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block px-3 py-2 text-gray-300 hover:text-white">Login</a>
                    <a href="register.php" class="block px-3 py-2 bg-blue-500 text-white rounded-lg text-center">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
       <!-- Hero Section -->
    <div class="relative min-h-screen pt-16 hero-gradient">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="text-center md:text-left animate__animated animate__fadeInLeft">
                    <h1 class="text-4xl md:text-6xl font-bold text-white leading-tight">
                        Transform Your
                        <span class="gradient-text">Learning Experience</span>
                    </h1>
                    <p class="mt-6 text-lg md:text-xl text-gray-300">
                        Experience the future of online assessment with our advanced examination platform. 
                        Secure, efficient, and user-friendly.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row justify-center md:justify-start space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="register.php" 
                           class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition duration-150 ease-in-out">
                            Get Started Free
                            <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="#features" 
                           class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-blue-300 bg-blue-900/30 hover:bg-blue-900/50 transition duration-150 ease-in-out">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="hidden md:block animate__animated animate__fadeInRight">
                    <img src="assets/images/hero-illustration.svg" alt="Online Examination" class="w-full animate-float">
                </div>
            </div>
        </div>

        <!-- Wave Separator -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg class="w-full h-24 fill-white" viewBox="0 0 1440 74" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,32L48,37.3C96,43,192,53,288,53.3C384,53,480,43,576,42.7C672,43,768,53,864,53.3C960,53,1056,43,1152,37.3C1248,32,1344,32,1392,32L1440,32L1440,74L1392,74C1344,74,1248,74,1152,74C1056,74,960,74,864,74C768,74,672,74,576,74C480,74,384,74,288,74C192,74,96,74,48,74L0,74Z"></path>
            </svg>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Total Exams -->
                <div class="bg-primary-dark rounded-xl p-6 text-center card-hover">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-500/20 mb-4">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold text-white"><?php echo number_format($stats['total_exams']); ?></h3>
                    <p class="mt-2 text-blue-300">Total Exams</p>
                </div>

                <!-- Total Students -->
                <div class="bg-primary-dark rounded-xl p-6 text-center card-hover">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-500/20 mb-4">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold text-white"><?php echo number_format($stats['total_students']); ?></h3>
                    <p class="mt-2 text-blue-300">Active Students</p>
                </div>

                <!-- Total Instructors -->
                <div class="bg-primary-dark rounded-xl p-6 text-center card-hover">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-500/20 mb-4">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold text-white"><?php echo number_format($stats['total_instructors']); ?></h3>
                    <p class="mt-2 text-blue-300">Expert Instructors</p>
                </div>

                <!-- Completed Exams -->
                <div class="bg-primary-dark rounded-xl p-6 text-center card-hover">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-500/20 mb-4">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold text-white"><?php echo number_format($stats['completed_exams']); ?></h3>
                    <p class="mt-2 text-blue-300">Completed Exams</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-primary-dark">Why Choose ExamMaster?</h2>
                <p class="mt-4 text-lg text-gray-600">Discover the features that make our platform stand out</p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-primary-dark">Secure Testing</h3>
                    <p class="mt-4 text-gray-600">Advanced security measures to ensure exam integrity and prevent cheating.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-primary-dark">Real-time Results</h3>
                    <p class="mt-4 text-gray-600">Instant grading and detailed performance analytics available immediately.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-primary-dark">Multiple Question Types</h3>
                    <p class="mt-4 text-gray-600">Support for various question formats including MCQ, essay, and more.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Exams Section -->
    <div id="exams" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-primary-dark">Featured Exams</h2>
                <p class="mt-4 text-lg text-gray-600">Browse our latest examination offerings</p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($featured_exams as $exam): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover border border-gray-100">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-semibold text-primary-dark">
                                        <?php echo htmlspecialchars($exam['title']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        by <?php echo htmlspecialchars($exam['instructor_name']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="text-gray-600">
                                    <?php echo htmlspecialchars(substr($exam['description'], 0, 100)) . '...'; ?>
                                </p>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4">
                                <div class="text-center p-2 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-500">Questions</p>
                                    <p class="mt-1 text-xl font-semibold text-primary-dark">
                                        <?php echo $exam['question_count']; ?>
                                    </p>
                                </div>
                                <div class="text-center p-2 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-500">Duration</p>
                                    <p class="mt-1 text-xl font-semibold text-primary-dark">
                                        <?php echo $exam['duration']; ?> min
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <a href="exam_details.php?id=<?php echo $exam['id']; ?>" 
                                   class="block w-full text-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition duration-150 ease-in-out">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
