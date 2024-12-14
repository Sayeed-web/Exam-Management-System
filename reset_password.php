<?php
session_start();
require 'db.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE reset_token = ? 
            AND reset_token_expiry > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, reset_token = NULL, reset_token_expiry = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$hashed_password, $user['id']]);

            $_SESSION['message'] = 'Password reset successful. Please login with your new password.';
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['error'] = 'Invalid or expired reset token.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div>
            <label>New Password:</label>
            <input type="password" name="new_password" required>
        </div>
        <div>
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
