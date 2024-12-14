<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$exam_id = $_GET['id'] ?? null;

if ($exam_id) {
    // Check if exam belongs to this instructor
    $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $exam = $stmt->fetch();

    if ($exam) {
        $pdo->beginTransaction();
        try {
            // Delete answer submissions
            $stmt = $pdo->prepare("
                DELETE ans FROM answer_submissions ans
                JOIN exam_submissions es ON ans.submission_id = es.id
                WHERE es.exam_id = ?
            ");
            $stmt->execute([$exam_id]);

            // Delete exam submissions
            $stmt = $pdo->prepare("DELETE FROM exam_submissions WHERE exam_id = ?");
            $stmt->execute([$exam_id]);

            // Delete question options
            $stmt = $pdo->prepare("
                DELETE qo FROM question_options qo
                JOIN questions q ON qo.question_id = q.id
                WHERE q.exam_id = ?
            ");
            $stmt->execute([$exam_id]);

            // Delete questions
            $stmt = $pdo->prepare("DELETE FROM questions WHERE exam_id = ?");
            $stmt->execute([$exam_id]);

            // Delete exam
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$exam_id]);

            $pdo->commit();
            $_SESSION['message'] = 'Exam deleted successfully!';
        } catch(Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error deleting exam: ' . $e->getMessage();
        }
    }
}

header('Location: manage_exams.php');
exit;
?>
