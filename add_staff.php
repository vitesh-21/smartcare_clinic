<?php
include 'db.php';
session_start();

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $role = trim($_POST['role']);

    if (!empty($full_name) && !empty($email) && !empty($role)) {
        // Prepare insert statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "INSERT INTO staff (full_name, email, department_id, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssis", $full_name, $email, $department_id, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Staff member registered successfully!</div>";
        } else {
            $message = "<div class='alert error'><i class='fa-solid fa-circle-xmark'></i> Error adding record: " . mysqli_error($conn) . "</div>";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Please fill in all required fields.</div>";
    }
}

// Fetch departments for the dropdown menu
$dept_query = mysqli_query($conn, "SELECT id, department_name FROM departments ORDER BY department_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCare - Add Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .form-container {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            color: #071739;
        }
        .form-header i {
            font-size: 28px;
            color: #2563eb;
        }
        .form-header h2 {
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
            font-size: 14px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-submit {
            background: #2563eb;
            color: white;
        }
        .btn-submit:hover {
            background: #1d4ed8;
        }
        .btn-cancel {
            background: #e2e8f0;
            color: #475569;
        }
        .btn-cancel:hover {
            background: #cbd5e1;
        }
        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="form-header">
        <i class="fa-solid fa-user-plus"></i>
        <h2>Add New Staff</h2>
    </div>

    <?php echo $message; ?>

    <form action="add_staff.php" method="POST">
        
        <div class="form-group">
            <label for="full_name">Full Name <span style="color:red;">*</span></label>
            <input type="text" id="full_name" name="full_name" placeholder="e.g. Dr. Michael Smith" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address <span style="color:red;">*</span></label>
            <input type="email" id="email" name="email" placeholder="e.g. michael@smartcare.com" required>
        </div>

        <div class="form-group">
            <label for="department_id">Assigned Department</label>
            <select id="department_id" name="department_id">
                <option value="">-- Select Department / General Admin --</option>
                <?php 
                if ($dept_query && mysqli_num_rows($dept_query) > 0) {
                    while ($dept = mysqli_fetch_assoc($dept_query)) {
                        echo "<option value='" . $dept['id'] . "'>" . htmlspecialchars($dept['department_name']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="role">Role / Job Title <span style="color:red;">*</span></label>
            <select id="role" name="role" required>
                <option value="">-- Choose Role --</option>
                <option value="Doctor">Doctor</option>
                <option value="Nurse">Nurse</option>
                <option value="Surgeon">Surgeon</option>
                <option value="Pharmacist">Pharmacist</option>
                <option value="Lab Technician">Lab Technician</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Administrator">Administrator</option>
            </select>
        </div>

        <div class="btn-container">
            <a href="admin.php?page=staff" class="btn btn-cancel">Back</a>
            <button type="submit" class="btn btn-submit">Save Staff</button>
        </div>

    </form>
</div>

</body>
</html>