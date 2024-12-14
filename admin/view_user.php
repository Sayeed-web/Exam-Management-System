<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header('Location: manage_users.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM exams WHERE instructor_id = u.id) as exams_created,
           (SELECT COUNT(*) FROM exam_submissions WHERE student_id = u.id) as exams_taken
    FROM users u
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: manage_users.php');
    exit;
}

// Get exam history based on role
if ($user['role'] == 'student') {
    // Get exams taken by student
    $stmt = $pdo->prepare("
        SELECT 
            es.*,
            e.title as exam_title,
            e.passing_score,
            u.username as instructor_name,
            (SELECT SUM(points) FROM questions WHERE exam_id = e.id) as total_points
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        JOIN users u ON e.instructor_id = u.id
        WHERE es.student_id = ?
        ORDER BY es.submit_time DESC
    ");
    $stmt->execute([$user_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else if ($user['role'] == 'instructor') {
    // Get exams created by instructor
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
            (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.id) as submission_count
        FROM exams e
        WHERE e.instructor_id = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- User Profile Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="text-gray-600">
                        <?php echo ucfirst($user['role']); ?> • 
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($user['role'] == 'student'): ?>
        <!-- Student Exams Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Exam Submissions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submit Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($exams as $exam): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($exam['instructor_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d H:i', strtotime($exam['submit_time'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $score = $exam['total_score'] ?? 0;
                                    $percentage = $exam['total_points'] > 0 
                                        ? ($score / $exam['total_points'] * 100) 
                                        : 0;
                                    echo number_format($percentage, 2) . '%';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $exam['status'] == 'graded' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($exam['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="view_submission.php?id=<?php echo $exam['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($user['role'] == 'instructor'): ?>
        <!-- Instructor Exams Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Created Exams</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($exams as $exam): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $exam['question_count']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $exam['submission_count']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d H:i', strtotime($exam['start_time'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d H:i', strtotime($exam['end_time'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="view_exam_details.php?id=<?php echo $exam['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
            <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                Edit User
            </a>
            <a href="manage_users.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                Back to Users List
            </a>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>