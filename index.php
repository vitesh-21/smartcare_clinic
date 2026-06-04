<?php
include 'db.php';
session_start();

$error = '';
$success = '';

// Fetch departments
$deptListResult = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");

if (isset($_POST['login'])) {

    $identifier   = trim($_POST['identifier'] ?? '');
    $password     = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? '';
    $selectedDept = trim($_POST['department'] ?? '');

    $isAdminLogin = false;

    /* ===================================================
       ADMIN LOGIN
    =================================================== */
    if ($selectedRole === 'admin') {

        $adminCheckStmt = $conn->prepare("
            SELECT * FROM users 
            WHERE role = 'admin' 
            AND (LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?))
            LIMIT 1
        ");

        $adminCheckStmt->bind_param("ss", $identifier, $identifier);
        $adminCheckStmt->execute();
        $adminResult = $adminCheckStmt->get_result();

        if ($adminResult->num_rows === 1) {

            $adminUser = $adminResult->fetch_assoc();

            if (password_verify($password, $adminUser['password'])) {

                // ✅ FIXED SESSION (NO MORE $user BUG)
                $_SESSION['user_id']   = $adminUser['id'];
                $_SESSION['staff_id']  = $adminUser['id'];
                $_SESSION['username']  = $adminUser['username'];
                $_SESSION['full_name'] = $adminUser['full_name'] ?? $adminUser['username'];
                $_SESSION['role']      = 'admin';
                $_SESSION['dept_id']   = $adminUser['dept_id'] ?? null;

                header("Location: admin.php");
                exit;

            } else {
                $error = "Admin password incorrect.";
                $isAdminLogin = true;
            }

        } else {

            // fallback admin
            if (
                (strtolower($identifier) === 'admin' || strtolower($identifier) === 'admin@smartcare.com')
                && $password === '1234'
            ) {
                $_SESSION['user_id']   = 1;
                $_SESSION['staff_id']  = 1;
                $_SESSION['username']  = 'admin';
                $_SESSION['full_name'] = 'System Administrator';
                $_SESSION['role']      = 'admin';
                $_SESSION['dept_id']   = 1;

                header("Location: admin.php");
                exit;

            } else {
                $error = "Invalid administrator credentials.";
                $isAdminLogin = true;
            }
        }
    }

    /* ===================================================
       STAFF LOGIN
    =================================================== */
    if (!$isAdminLogin && !empty($identifier)) {

        $stmt = $conn->prepare("
            SELECT * FROM users 
            WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)
            LIMIT 1
        ");

        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                if (strtolower($user['role']) !== strtolower($selectedRole)) {
                    $error = "Role mismatch! You are registered as: " . ucfirst($user['role']);
                } else {

                    // ✅ FIXED SESSION (IMPORTANT)
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['staff_id']  = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['dept_id']   = $user['dept_id'] ?? null;

                    /* =========================
                       IN-CHARGE CHECK
                    ========================= */
                    if ($user['role'] === 'staff') {

                        if (empty($selectedDept)) {
                            $error = "You must select your department.";
                            session_destroy();
                        } else {

                            $deptStmt = $conn->prepare("
                                SELECT in_charge_staff_id 
                                FROM departments 
                                WHERE department_name = ?
                                LIMIT 1
                            ");

                            $deptStmt->bind_param("s", $selectedDept);
                            $deptStmt->execute();
                            $deptResult = $deptStmt->get_result();

                            if ($deptResult->num_rows === 1) {

                                $deptData = $deptResult->fetch_assoc();

                                if ((int)$user['id'] === (int)$deptData['in_charge_staff_id']) {

                                    $_SESSION['assigned_department'] = $selectedDept;

                                    $targetDashboard = str_replace(' ', '', $selectedDept) . '.php';

                                    header("Location: $targetDashboard");
                                    exit;

                                } else {
                                    $error = "Access Denied! You are not assigned as in-charge for this department.";
                                }

                            } else {
                                $error = "Department not found in system.";
                            }
                        }

                    } else {
                        header("Location: dashboard.php");
                        exit;
                    }
                }

            } else {
                $error = "Wrong password.";
            }

        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartCare Clinic | Login</title>
<link rel="stylesheet" href="style.css">

<style>
/* ==================================================
   STYLING BLOCKS (Preserved custom layout geometry)
================================================== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body.auth-login{
    font-family:'Segoe UI', sans-serif;
    background:#eef2f7;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:25px;
    overflow:hidden;
    position:relative;
}

body.auth-login::before{
    content:'';
    position:fixed;
    inset:0;
    background:
        radial-gradient(circle at top left, rgba(59,130,246,0.08), transparent 30%),
        radial-gradient(circle at bottom right, rgba(16,37,66,0.08), transparent 30%);
    z-index:-1;
}

.auth-shell{
    width:100%;
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.auth-container{
    width:1100px;
    height:680px;
    display:flex;
    gap:18px;
    padding:18px;
    border-radius:34px;
    background:rgba(255,255,255,0.40);
    backdrop-filter:blur(12px);
    box-shadow:0 25px 60px rgba(15,23,42,0.10);
    overflow:hidden;
}

.auth-hero{
    flex:1;
    background:linear-gradient(145deg, #081120, #0a1931, #102542);
    border-radius:28px;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:3.5rem;
    position:relative;
    overflow:hidden;
}

.auth-hero::before{
    content:'';
    position:absolute;
    width:430px;
    height:430px;
    border-radius:50%;
    background:rgba(255,255,255,0.03);
    top:-180px;
    right:-100px;
}

.auth-hero::after{
    content:'';
    position:absolute;
    width:280px;
    height:280px;
    border-radius:50%;
    background:rgba(255,255,255,0.02);
    bottom:-100px;
    left:-100px;
}

.auth-hero-content{
    position:relative;
    z-index:2;
    max-width:470px;
}

.auth-brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:1.7rem;
    font-weight:700;
    margin-bottom:1.8rem;
}

.auth-brand-badge{
    width:15px;
    height:15px;
    border-radius:5px;
    background:#3b82f6;
    box-shadow:0 0 18px rgba(59,130,246,0.8);
}

.hero-pill{
    display:inline-block;
    padding:10px 18px;
    border-radius:999px;
    background:rgba(255,255,255,0.08);
    color:rgba(255,255,255,0.92);
    margin-bottom:2rem;
    font-size:13px;
    font-weight:500;
}

.auth-hero h1{
    font-size:3.3rem;
    line-height:1.05;
    margin-bottom:1.2rem;
    font-weight:800;
    letter-spacing:-2px;
}

.auth-hero p{
    font-size:0.98rem;
    line-height:1.8;
    color:rgba(255,255,255,0.74);
}

.auth-panel{
    width:400px;
    background:white;
    border-radius:28px;
    padding:2.7rem;
    display:flex;
    flex-direction:column;
    justify-content:center;
    box-shadow:0 20px 40px rgba(0,0,0,0.06);
}

.auth-panel h2{
    font-size:1.8rem;
    color:#0a1931;
    margin-bottom:0.4rem;
}

.auth-panel p{
    color:#6b7280;
    margin-bottom:1.7rem;
    font-size:0.95rem;
}

.field{
    margin-bottom:1.2rem;
}

.field label{
    display:block;
    margin-bottom:0.55rem;
    font-weight:600;
    color:#111827;
    font-size:0.94rem;
}

.field input,
.field select{
    width:100%;
    padding:0.9rem 1rem;
    border:1px solid #dbe2ea;
    border-radius:14px;
    background:#f7f9fc;
    font-size:0.95rem;
    transition:0.3s ease;
    outline:none;
}

.field input:focus,
.field select:focus{
    border-color:#2563eb;
    background:white;
    box-shadow:0 0 0 4px rgba(37,99,235,0.10);
}

.password-input{
    position:relative;
}

.password-toggle{
    position:absolute;
    top:50%;
    right:15px;
    transform:translateY(-50%);
    border:none;
    background:none;
    cursor:pointer;
}

.password-toggle svg{
    width:20px;
    height:20px;
    stroke:#6b7280;
    fill:none;
    stroke-width:2;
}

.button.primary{
    width:100%;
    padding:0.95rem;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg, #081120, #102542);
    color:white;
    font-size:0.95rem;
    font-weight:700;
    cursor:pointer;
    transition:0.3s ease;
}

.button.primary:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 24px rgba(8,17,32,0.25);
}

.message{
    padding:0.95rem;
    border-radius:14px;
    margin-bottom:1.3rem;
    font-size:0.92rem;
}

.success{ background:#dcfce7; color:#166534; }
.error{ background:#fee2e2; color:#991b1b; }

.auth-links{
    margin-top:1.7rem;
    text-align:center;
    font-size:0.9rem;
}

.auth-links a{
    color:#0a1931;
    text-decoration:none;
    font-weight:600;
}

.auth-links a:hover{ text-decoration:underline; }

@media(max-width:1000px){
    body.auth-login{ overflow:auto; }
    .auth-container{ width:100%; height:auto; flex-direction:column; }
    .auth-panel{ width:100%; }
    .auth-hero{ min-height:320px; padding:3rem 2rem; }
    .auth-hero h1{ font-size:2.8rem; }
}
</style>
</head>

<body class="auth-login">

<div class="auth-shell">
    <div class="auth-container">

        <div class="auth-hero">
            <div class="auth-hero-content">
                <div class="auth-brand">
                    <span class="auth-brand-badge"></span>
                    SmartCare Clinic
                </div>
                <div class="hero-pill">Smart Healthcare Management</div>
                <h1>Welcome Back</h1>
                <p>Sign in to manage patients, appointments, pharmacy, laboratory operations, billing, analytics, and staff activities.</p>
            </div>
        </div>

        <div class="auth-panel">
            <div>
                <h2>Login</h2>
                <p>Access your SmartCare dashboard securely.</p>
            </div>

            <?php if ($success): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="">

                <div class="field">
                    <label>Username or Email</label>
                    <input type="text" name="identifier" required autocomplete="username" placeholder="Enter username or email">
                </div>

                <div class="field">
                    <label>Password</label>
                    <div class="password-input">
                        <input id="loginPassword" type="password" name="password" required autocomplete="current-password" placeholder="Enter password">
                        <button class="password-toggle" type="button" data-target="loginPassword" aria-label="Show password">
                            <svg viewBox="0 0 24 24">
                                <path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/>
                                <circle cx="12" cy="12" r="2.6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label>Login As</label>
                    <select id="loginType" onchange="toggleRoleOptions()" required>
                        <option value="" disabled selected>Select login type</option>
                        <option value="admin">Administrator</option>
                        <option value="staff">Staff Member</option>
                    </select>
                </div>

                <div class="field" id="deptField" style="display:none;">
                    <label>Department Assigned (In-Charge)</label>
                    <select name="department" id="deptSelect">
                        <option value="" disabled selected>Select your managed department</option>
                        <?php 
                        if ($deptListResult && $deptListResult->num_rows > 0) {
                            while ($row = $deptListResult->fetch_assoc()) {
                                $deptName = htmlspecialchars($row['department_name']);
                                echo "<option value=\"$deptName\">$deptName</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <input type="hidden" name="role" id="hiddenRoleField">

                <div class="auth-actions">
                    <button class="button primary" type="submit" name="login">Login</button>
                </div>

            </form>

            <div class="auth-links">
                <a href="forgotpassword.php">Forgot Password?</a>
                &middot;
                <a href="signup.php">Create Account</a>
            </div>

        </div>
    </div>
</div>

<script>
// Password display toggle initialization 
(function () {
    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
})();

/* ====================================
   CLEANED SELECTION CONTROLLER
==================================== */
function toggleRoleOptions(){
    const loginType = document.getElementById('loginType').value;
    const deptField = document.getElementById('deptField');
    const deptSelect = document.getElementById('deptSelect');
    const hiddenRoleField = document.getElementById('hiddenRoleField');

    if (loginType === 'admin') {
        hiddenRoleField.value = 'admin';
        deptField.style.display = 'none';
        deptSelect.removeAttribute('required');
        deptSelect.value = '';
    } else if (loginType === 'staff') {
        hiddenRoleField.value = 'staff';
        deptField.style.display = 'block';
        deptSelect.setAttribute('required', true);
    } else {
        hiddenRoleField.value = '';
        deptField.style.display = 'none';
        deptSelect.removeAttribute('required');
    }
}
</script>

</body>
</html>

```