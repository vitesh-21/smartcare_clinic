<?php
include 'db.php';
session_start();

date_default_timezone_set('Africa/Nairobi');

$message = '';
$messageClass = '';
$emailVerified = false;
$verifiedEmail = '';

// STEP 1: VERIFY IF THE EMAIL EXISTS
if (isset($_POST['verify_email'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageClass = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $emailVerified = true;
            $verifiedEmail = $email;
            $message = 'Email verified successfully! Please enter your new password below.';
            $messageClass = 'success';
        } else {
            $message = 'No account found with that email address.';
            $messageClass = 'error';
        }
        $stmt->close();
    }
}

// STEP 2: UPDATE THE PASSWORD
if (isset($_POST['change_password'])) {
    $email = trim($_POST['verified_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all password fields.';
        $messageClass = 'error';
        $emailVerified = true; // Keep the form open
        $verifiedEmail = $email;
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match. Please try again.';
        $messageClass = 'error';
        $emailVerified = true; // Keep the form open
        $verifiedEmail = $email;
    } else {
        // Securely hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $email);

        if ($updateStmt->execute()) {
            $message = 'Password changed successfully! You can now log in.';
            $messageClass = 'success';
            // $emailVerified remains false to close the reset input form view
        } else {
            $message = 'Something went wrong while updating the password.';
            $messageClass = 'error';
            $emailVerified = true;
        }
        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | City Clinic</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
    body{
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:24px;
        background:
            linear-gradient(135deg, rgba(8,20,32,.06), rgba(255,255,255,.03)),
            url("https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80") center/cover fixed no-repeat,
            #e0f0ff;
    }
    .login-box{
        width:min(420px, 100%);
        background:rgba(255,255,255,.1);
        border-radius:24px;
        padding:42px 34px;
        border:1px solid rgba(255,255,255,.34);
        box-shadow:0 30px 90px rgba(15,23,42,.16);
        backdrop-filter:blur(16px);
    }
    .logo{text-align:center;margin-bottom:24px;}
    .logo i{
        font-size:56px;
        color:#0b6c66;
        text-shadow:0 10px 24px rgba(15,118,110,.2);
    }
    .logo h2{
        color:#0b6c66;
        font-size:28px;
        margin-top:10px;
    }
    .input-group{position:relative;margin-bottom:20px;}
    .input-group input{
        width:100%;
        padding:14px 15px;
        border:1px solid rgba(31,124,198,.18);
        border-radius:14px;
        background:rgba(255,255,255,.32);
        color:#0b6c66;
        font-size:14px;
        outline:none;
    }
    .input-group label{
        position:absolute;
        top:50%;
        left:15px;
        color:#1f7cc6;
        transform:translateY(-50%);
        transition:0.3s;
        pointer-events:none;
    }
    .input-group input:focus ~ label,
    .input-group input:on-entry ~ label,
    .input-group input:valid ~ label{
        top:-10px;
        left:12px;
        font-size:12px;
        color:#0b6c66;
    }
    button{
        width:100%;
        padding:16px;
        border:none;
        border-radius:16px;
        background:linear-gradient(135deg, #0b6c66, #1f7cc6);
        color:#fff;
        font-weight:bold;
        font-size:16px;
        cursor:pointer;
        transition:all 0.3s;
        box-shadow:0 12px 24px rgba(15,23,42,.12);
    }
    button:hover{
        box-shadow:0 16px 34px rgba(15,23,42,.16);
        transform:scale(1.03);
    }
    .success,.error{
        padding:12px;
        text-align:center;
        border-radius:12px;
        margin-bottom:15px;
        font-weight:500;
    }
    .success{ background:rgba(220,252,231,.85); color:#166534; }
    .error{ background:rgba(254,226,226,.85); color:#991b1b; }
    .links{text-align:center;margin-top:15px;}
    .links a{color:#1f7cc6;text-decoration:none;font-size:14px;}
    .links a:hover{color:#0b6c66;text-decoration:underline;}
</style>
</head>
<body>

<div class="login-box">
  <div class="logo"><i class="fas fa-hospital-symbol"></i><h2>City Clinic</h2></div>
  
  <p style="text-align:center;color:#333;font-size:14px;margin-bottom:22px;font-weight:600;">
    Account Security Recovery
  </p>

  <?php if ($message): ?>
      <div class="<?= htmlspecialchars($messageClass) ?>">
          <?= htmlspecialchars($message) ?>
      </div>
  <?php endif; ?>

  <?php if (!$emailVerified): ?>
      <form method="post" action="">
        <div class="input-group">
          <input type="email" name="email" required="required">
          <label>Enter Registered Email</label>
        </div>
        <button type="submit" name="verify_email">Verify Account</button>
      </form>
  <?php endif; ?>

  <?php if ($emailVerified): ?>
      <form method="post" action="">
          <input type="hidden" name="verified_email" value="<?= htmlspecialchars($verifiedEmail) ?>">
          
          <div class="input-group">
              <input type="password" name="new_password" required="required">
              <label>New Password</label>
          </div>

          <div class="input-group">
              <input type="password" name="confirm_password" required="required">
              <label>Confirm New Password</label>
          </div>

          <button type="submit" name="change_password">Update Password</button>
      </form>
  <?php endif; ?>

  <div class="links">
    <a href="index.php">Back to Login</a>
  </div>
</div>

</body>
</html>