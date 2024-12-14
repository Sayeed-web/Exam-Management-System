<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmt->execute([$token, $user['id']]);

        // Send reset email (you'll need to configure email settings)
        $reset_link = "http://yourwebsite.com/reset_password.php?token=" . $token;
        $to = $email;
        $subject = "Password Reset Request";
        $message = "Click the following link to reset your password: " . $reset_link;
        mail($to, $subject, $message);

        $_SESSION['message'] = 'Password reset instructions have been sent to your email.';
    } else {
        $_SESSION['error'] = 'Email address not found.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <h2>Reset Password</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: green"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label>Email Address:</label>
            <input type="email" name="email" required>
        </div>
        <button type="submit">Request Reset</button>
    </form>
</body>
</html>
