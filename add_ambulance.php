<?php
include 'db.php';
session_start();

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate_number = trim($_POST['plate_number']);
    $driver_name = trim($_POST['driver_name']);
    
    // All newly registered ambulances start as clean/available assets
    $status = 'Available'; 

    if (!empty($plate_number) && !empty($driver_name)) {
        // Prevent SQL Injection using a prepared statement
        $stmt = mysqli_prepare($conn, "INSERT INTO ambulances (plate_number, status, driver_name) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $plate_number, $status, $driver_name);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Ambulance successfully registered into the fleet system!</div>";
        } else {
            $message = "<div class='alert error'><i class='fa-solid fa-circle-xmark'></i> Database Error: " . mysqli_error($conn) . "</div>";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Please fill out all required fields.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCare - Add Ambulance</title>
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
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .info-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
            margin-top: 4px;
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
        <i class="fa-solid fa-truck-medical"></i>
        <h2>Add Ambulance</h2>
    </div>

    <?php echo $message; ?>

    <form action="add_ambulance.php" method="POST">
        
        <div class="form-group">
            <label for="plate_number">Plate Number <span style="color:red;">*</span></label>
            <input type="text" id="plate_number" name="plate_number" placeholder="e.g. KAA 123A or ABC-1234" required>
        </div>

        <div class="form-group">
            <label for="driver_name">Assigned Driver Name <span style="color:red;">*</span></label>
            <input type="text" id="driver_name" name="driver_name" placeholder="e.g. John Doe" required>
        </div>

        <div class="form-group">
            <label>Smart Fleet Status Tracking</label>
            <div>
                <span class="info-badge">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> This vehicle automatically shifts to <strong>Occupied</strong> whenever assigned to an active patient referral trip, otherwise it rests at <strong>Available</strong>.
                </span>
            </div>
        </div>

        <div class="btn-container">
            <a href="admin.php?page=ambulance" class="btn btn-cancel">Back Dashboard</a>
            <button type="submit" class="btn btn-submit">Save Vehicle</button>
        </div>

    </form>
</div>

</body>
</html>