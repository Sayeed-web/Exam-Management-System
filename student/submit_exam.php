<?php
session_start();
require '../db.php';

if ($_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: view_exams.php');
    exit;
}

$exam_id = $_POST['exam_id'];
$submission_id = $_POST['submission_id'];
$answers = $_POST['answers'] ?? [];

$pdo->beginTransaction();
try {
    // Save answers
    foreach ($answers as $question_id => $answer) {
        if (is_array($answer)) { // Multiple choice
            $stmt = $pdo->prepare("
                INSERT INTO answer_submissions (submission_id, question_id, selected_option_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$submission_id, $question_id, $answer['option_id']]);
        } else { // Essay or True/False
            $stmt = $pdo->prepare("
                INSERT INTO answer_submissions (submission_id, question_id, answer_text)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$submission_id, $question_id, $answer]);
        }
    }

    // Auto-grade multiple choice questions
    $stmt = $pdo->prepare("
        UPDATE answer_submissions ans
        JOIN questions q ON ans.question_id = q.id
        JOIN question_options qo ON ans.selected_option_id = qo.id
        SET ans.score = CASE 
            WHEN qo.is_correct = 1 THEN q.points
            ELSE 0
        END
        WHERE ans.submission_id = ?
        AND q.question_type = 'multiple_choice'
    ");
    $stmt->execute([$submission_id]);

    // Update submission status
    $stmt = $pdo->prepare("
        UPDATE exam_submissions 
        SET status = 'submitted', submit_time = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$submission_id]);

    $pdo->commit();
    $_SESSION['message'] = 'Exam submitted successfully!';
    header('Location: view_results.php');
    exit;
} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error submitting exam: ' . $e->getMessage();
    header("Location: take_exam.php?exam_id=$exam_id");
    exit;
}
?>
