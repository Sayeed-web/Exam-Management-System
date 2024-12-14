<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'instructor') {
    header('Location: ../login.php');
    exit;
}

$question_id = $_GET['id'] ?? null;
$exam_id = $_GET['exam_id'] ?? null;

if ($question_id) {
    // Verify the question belongs to an exam owned by this instructor
    $stmt = $pdo->prepare("
        SELECT q.id 
        FROM questions q
        JOIN exams e ON q.exam_id = e.id
        WHERE q.id = ? AND e.instructor_id = ?
    ");
    $stmt->execute([$question_id, $_SESSION['user_id']]);
    $question = $stmt->fetch();

    if ($question) {
        $pdo->beginTransaction();
        try {
            // Delete answer submissions for this question
            $stmt = $pdo->prepare("
                DELETE ans FROM answer_submissions ans
                WHERE ans.question_id = ?
            ");
            $stmt->execute([$question_id]);

            // Delete question options
            $stmt = $pdo->prepare("DELETE FROM question_options WHERE question_id = ?");
            $stmt->execute([$question_id]);

            // Delete question
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);

            $pdo->commit();
            $_SESSION['message'] = 'Question deleted successfully!';
        } catch(Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error deleting question: ' . $e->getMessage();
        }
    }
}

header('Location: edit_exam.php?id=' . $exam_id);
exit;
?>
