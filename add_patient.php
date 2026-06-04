<?php
include 'db.php';
session_start();

// Admin security check matching your dashboard
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 
$is_admin = true; // Temporary fallback override matching your dashboard

$message = "";
$message_class = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    $gender    = mysqli_real_escape_string($conn, $_POST['gender']);
    $age       = intval($_POST['age']);
    $dept_id   = intval($_POST['department']);
    $bed_label = mysqli_real_escape_string($conn, $_POST['bed']); // Change to match your exact bed unique reference column

    if (!empty($full_name) && !empty($condition) && !empty($gender) && !empty($age)) {
        // 'condition' is typically an SQL reserved keyword, wrapped in backticks to prevent syntax failures
        $sql = "INSERT INTO patients (full_name, `condition`, gender, age, department_id, bed) 
                VALUES ('$full_name', '$condition', '$gender', $age, $dept_id, '$bed_label')";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Patient added successfully!";
            $message_class = "success-msg";
            
            // Optional: If you want to update the bed status to 'Occupied' dynamically when a patient is assigned
            // mysqli_query($conn, "UPDATE beds SET status = 'Occupied' WHERE bed_label = '$bed_label'");
        } else {
            $message = "Database Error: " . mysqli_error($conn);
            $message_class = "error-msg";
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_class = "error-msg";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Patient - SmartCare</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    body {
        display: flex;
        background: #f4f7fc;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }
    .form-box {
        background: white;
        width: 100%;
        max-width: 600px;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .form-box h2 {
        color: #071739;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
    }
    .form-control:focus {
        border-color: #2563eb;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .btn-submit {
        background: #2563eb;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        width: 100%;
        transition: background 0.3s;
    }
    .btn-submit:hover {
        background: #0b244f;
    }
    .back-link {
        display: inline-block;
        margin-top: 15px;
        color: #666;
        text-decoration: none;
        font-size: 14px;
    }
    .back-link:hover {
        color: #2563eb;
    }
    /* Alert Messages */
    .msg {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: bold;
    }
    .success-msg {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .error-msg {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }
</style>
</head>
<body>

<div class="form-box">
    <h2><i class="fa-solid fa-user-plus"></i> Add New Patient</h2>

    <?php if(!empty($message)): ?>
        <div class="msg <?php echo $message_class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="add_patient.php" method="POST">
        
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. John Doe" required>
        </div>

        <div class="form-group">
            <label for="condition">Condition</label>
            <select name="condition" id="condition" class="form-control" required>
                <option value="">-- Select Condition --</option>
                <option value="Stable">Stable</option>
                <option value="Critical">Critical</option>
                <option value="Under Observation">Under Observation</option>
                <option value="Serious">Serious</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gender">Gender</label>
                <select name="gender" id="gender" class="form-control" required>
                    <option value="">-- Select --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" name="age" id="age" class="form-control" min="0" max="150" placeholder="e.g. 45" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="department">Department</label>
                <select name="department" id="department" class="form-control" required>
                    <option value="">-- Select Department --</option>
                    <?php
                    // Fetch real departments directly from your database table
                    $dept_query = mysqli_query($conn, "SELECT id, department_name FROM departments");
                    while($dept = mysqli_fetch_assoc($dept_query)) {
                        echo "<option value='".$dept['id']."'>".htmlspecialchars($dept['department_name'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bed">Assigned Bed</label>
                <select name="bed" id="bed" class="form-control" required>
                    <option value="">-- Select Bed --</option>
                    <?php
                    // Fetch real beds directly from your database table (filtering out unassigned or available ones if desired)
                    $bed_query = mysqli_query($conn, "SELECT DISTINCT bed_label FROM beds WHERE status = 'Available'");
                    
                    // Fallback to fetch all beds if you aren't strict on state yet
                    if(mysqli_num_rows($bed_query) == 0){
                        $bed_query = mysqli_query($conn, "SELECT DISTINCT bed_label FROM beds");
                    }

                    while($bed = mysqli_fetch_assoc($bed_query)) {
                        echo "<option value='".htmlspecialchars($bed['bed_label'])."'>".htmlspecialchars($bed['bed_label'])."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-submit">Register Patient</button>
    </form>

    <a href="admin.php?page=patients" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Patient Dashboard</a>
</div>

</body>
</html>