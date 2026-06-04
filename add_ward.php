<?php
include 'db.php';
session_start();

// 1. Find the next sequential block letter across the hospital
$block_query = mysqli_query($conn, "SELECT DISTINCT block_name FROM wards ORDER BY block_name DESC LIMIT 1");
if (mysqli_num_rows($block_query) > 0) {
    $last_block = mysqli_fetch_assoc($block_query)['block_name'];
    $next_suggested_block = ++$last_block; // Increments string letter sequentially (e.g., 'B' becomes 'C')
} else {
    $next_suggested_block = 'A'; // Start at Block A if system is brand new
}

$message = "";

// 2. Handle the Block Allocation Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $department_id = intval($_POST['department_id']);
    $block_name = strtoupper(trim($_POST['block_name']));

    // Enforce Rule: Every department is strictly limited to TWO blocks max
    $dept_check = mysqli_query($conn, "SELECT COUNT(DISTINCT block_name) as total_blocks FROM wards WHERE department_id = $department_id");
    $dept_data = mysqli_fetch_assoc($dept_check);
    
    if ($dept_data['total_blocks'] >= 2) {
        $message = "<div class='alert error'>Allocation Rejected: This department already has its maximum allowance of 2 Blocks (6 Floors / 180 Beds)!</div>";
    } else {
        // Flag to trace successful loops
        $all_floors_saved = true;
        
        // Loop to automatically build all 3 Floors for this block
        for ($floor = 1; $floor <= 3; $floor++) {
            $insert_ward = mysqli_query($conn, "INSERT INTO wards (department_id, block_name, floor) VALUES ($department_id, '$block_name', $floor)");
            
            if ($insert_ward) {
                $ward_id = mysqli_insert_id($conn);
                
                // Nested Loop: Automatically build 30 sequential beds for this floor
                for ($bed = 1; $bed <= 30; $bed++) {
                    $bed_label = "Bed-" . $block_name . "-F" . $floor . "-" . str_pad($bed, 2, '0', STR_PAD_LEFT);
                    mysqli_query($conn, "INSERT INTO beds (ward_id, bed_label, status) VALUES ($ward_id, '$bed_label', 'Available')");
                }
            } else {
                $all_floors_saved = false;
            }
        }

        if ($all_floors_saved) {
            $message = "<div class='alert success'>Success! Block $block_name fully initialized with 3 Floors and 90 Automated Sequential Beds.</div>";
            header("Refresh:2; url=admin.php?page=wards");
        } else {
            $message = "<div class='alert error'>Database Error: Failed to cleanly populate all structural assets.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SmartCare | Automatic Block Initialization</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fc; padding: 40px; display: flex; justify-content: center; }
        .form-box { background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #071739; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .rule-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #1e40af; line-height: 1.4; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 14px; }
        select, input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; }
        input[readonly] { background: #e2e8f0; color: #475569; font-weight: bold; }
        .btn { width: 100%; background: #2563eb; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #1d4ed8; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        .success { background: #dcfce7; color: #15803d; border-left: 4px solid #22c55e; }
        .error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
        .back-link { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #071739; }
    </style>
</head>
<body>

<div class="form-box">
    <h2><i class="fa-solid fa-building-shield"></i> Allocate Department Block</h2>
    
    <div class="rule-box">
        <strong><i class="fa-solid fa-circle-info"></i> Hospital Layout Architecture:</strong><br>
        1. Selecting a department assigns the next global block letter.<br>
        2. **Automation Active:** System will automatically generate all 3 floors inside this block.<br>
        3. **Bed Allocation:** 90 trackable beds will be instantly mapped to this department.<br>
        4. Every department is capped at a maximum of 2 blocks.
    </div>

    <?php echo $message; ?>

    <form action="add_ward.php" method="POST">
        <div class="form-group">
            <label>Select Department</label>
            <select name="department_id" required>
                <option value="">-- Choose Target Department --</option>
                <?php
                $depts = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");
                while($d = mysqli_fetch_assoc($depts)){
                    echo "<option value='".$d['id']."'>".htmlspecialchars($d['department_name'])."</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Next Sequential Block Assignment (Enforced)</label>
            <input type="text" name="block_name" value="<?php echo $next_suggested_block; ?>" readonly>
        </div>

        <button type="submit" class="btn"><i class="fa-solid fa-bolt"></i> Provision Block & 90 Beds</button>
    </form>
    
    <a href="admin.php?page=wards" class="back-link"><i class="fa-solid fa-arrow-left"></i> Return to structural dashboard</a>
</div>

</body>
</html>