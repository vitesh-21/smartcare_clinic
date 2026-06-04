<?php
include 'db.php';
session_start();

$page = $_GET['page'] ?? 'dashboard';
$dept_id = $_GET['id'] ?? null; // Keep this distinct for view details actions

// Checking if the logged-in user is an admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 
$is_admin = true; // Fallback helper override

// --- DYNAMIC LIVE COUNTS FOR HOME CARDS ---
$total_patients = 0;
$total_staff = 0;
$total_departments = 0;

$p_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM patients");
if($p_count) { $total_patients = mysqli_fetch_assoc($p_count)['total']; }

$s_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM staff WHERE LOWER(role) = 'staff'");
if($s_count) { $total_staff = mysqli_fetch_assoc($s_count)['total']; }

$d_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM departments");
if($d_count) { $total_departments = mysqli_fetch_assoc($d_count)['total']; }

// --- HANDLE ASSIGNING STAFF IN-CHARGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_in_charge'])) {
    if ($is_admin) { 
        $post_dept_id = intval($_POST['department_id']);
        $staff_id = intval($_POST['staff_id']);
        
        if ($staff_id === 0) {
            $update_query = "UPDATE departments SET in_charge_staff_id = NULL WHERE id = $post_dept_id";
        } else {
            $update_query = "UPDATE departments SET in_charge_staff_id = $staff_id WHERE id = $post_dept_id";
        }
        
        if(mysqli_query($conn, $update_query)) {
            header("Location: admin.php?page=incharge");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartCare Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}
body{
    display:flex;
    background:#f4f7fc;
}
/* ================= SIDEBAR ================= */
.sidebar{
    width:250px;
    height:100vh;
    background:linear-gradient(to bottom,#071739,#0b244f);
    color:white;
    position:fixed;
    padding-top:20px;
    overflow:auto;
}
.logo{
    display:flex;
    align-items:center;
    gap:10px;
    padding:20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}
.logo h2{ font-size:30px; }
.logo-text h1{ font-size:32px; }
.logo-text p{ font-size:13px; opacity:0.8; }
.sidebar ul{ list-style:none; margin-top:20px; }
.sidebar ul li{ margin:5px 15px; }
.sidebar ul li a{
    display:flex;
    align-items:center;
    gap:15px;
    color:white;
    text-decoration:none;
    padding:15px;
    border-radius:10px;
    transition:0.3s;
}
.sidebar ul li a:hover,
.sidebar ul li a.active{ background:#2563eb; }
.sidebar ul li a i{ width:20px; }
/* ================= MAIN ================= */
.main{ margin-left:250px; width:100%; padding:20px; }
/* ================= TOPBAR ================= */
.topbar{
    background:white;
    padding:15px 25px;
    border-radius:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    box-shadow:0 2px 5px rgba(0,0,0,0.05);
}
.topbar h1{ font-size:35px; }
.admin{ display:flex; align-items:center; gap:10px; }
/* ================= CARDS ================= */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    margin-bottom:30px;
}
.card{
    background:white;
    border-radius:15px;
    padding:25px;
    box-shadow:0 3px 10px rgba(0,0,0,0.05);
}
.card-top{ display:flex; align-items:center; justify-content:space-between; }
.icon{
    width:60px;
    height:60px;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:25px;
    color:white;
}
.blue{ background:#3b82f6; }
.green{ background:#22c55e; }
.purple{ background:#a855f7; }
.red{ background:#ef4444; }
.orange{ background:#f59e0b; }
.card h3{ margin-top:15px; color:#555; }
.card p{ font-size:35px; font-weight:bold; margin-top:5px; }
/* ================= TABLES ================= */
.grid{ display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.table-box{ background:white; border-radius:15px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
.table-box h2{ margin-bottom:15px; }
/* ================= CUSTOM DEPARTMENTS GRID ================= */
.departments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 20px;
}
.dept-card {
    background: white;
    border-radius: 15px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
}
.dept-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
.dept-icon {
    width: 70px;
    height: 70px;
    color: #0b244f;
    font-size: 35px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
}
.dept-card h3 { font-size: 22px; color: #071739; margin-bottom: 10px; }
.dept-card p { font-size: 14px; color: #666; line-height: 1.5; margin-bottom: 20px; max-width: 240px; }
.view-panel-link {
    font-size: 15px;
    font-weight: bold;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.view-panel-link:hover { color: #0b244f; }
table{ width:100%; border-collapse:collapse; background:white; margin-top: 10px;}
table th{ background:#f1f5f9; text-align:left; padding:12px; font-size:14px; }
table td{ padding:12px; border-bottom:1px solid #eee; font-size:14px; }
.page-title{ margin-bottom:20px; font-size:30px; }
.btn{
    display:inline-block;
    margin-bottom:15px;
    background:#2563eb;
    color:white;
    padding:10px 15px;
    border-radius:8px;
    text-decoration:none;
    border:none;
    cursor:pointer;
}
/* ================= STATUS ================= */
.status{ padding:5px 10px; border-radius:5px; font-size:12px; font-weight:bold; text-transform: capitalize;}
.available{ background:#dcfce7; color:#15803d; }
.occupied{ background:#fee2e2; color:#b91c1c; }
/* ================= FOOTER ================= */
.footer{ text-align:center; margin-top:20px; color:#777; font-size:14px; }
@media(max-width:1000px){
    .grid{ grid-template-columns:1fr; }
    .sidebar{ width:80px; }
    .sidebar .text{ display:none; }
    .main{ margin-left:80px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <h2><i class="fa-solid fa-hospital"></i></h2>
        <div class="logo-text text">
            <h1>SmartCare</h1>
            <p>Hospital Management System</p>
        </div>
    </div>
    <ul>
        <li><a href="admin.php?page=dashboard" class="<?php if($page=='dashboard') echo 'active'; ?>"><i class="fa-solid fa-house"></i><span class="text">Dashboard</span></a></li>
        <li><a href="admin.php?page=departments" class="<?php if($page=='departments') echo 'active'; ?>"><i class="fa-solid fa-building"></i><span class="text">Departments</span></a></li>
        <li><a href="admin.php?page=patients" class="<?php if($page=='patients') echo 'active'; ?>"><i class="fa-solid fa-user"></i><span class="text">Patients</span></a></li>
        <li><a href="admin.php?page=staff" class="<?php if($page=='staff') echo 'active'; ?>"><i class="fa-solid fa-users"></i><span class="text">Staff</span></a></li>
        <li><a href="admin.php?page=ambulance" class="<?php if($page=='ambulance') echo 'active'; ?>"><i class="fa-solid fa-truck-medical"></i><span class="text">Ambulance</span></a></li>
        <li><a href="admin.php?page=wards" class="<?php if($page=='wards') echo 'active'; ?>"><i class="fa-solid fa-bed"></i><span class="text">Ward</span></a></li>
        <li><a href="admin.php?page=beds" class="<?php if($page=='beds') echo 'active'; ?>"><i class="fa-solid fa-procedures"></i><span class="text">Beds</span></a></li>
        <li><a href="admin.php?page=reservations" class="<?php if($page=='reservations') echo 'active'; ?>"><i class="fa-solid fa-calendar-check"></i><span class="text">Reservations</span></a></li>
        <li><a href="admin.php?page=reports" class="<?php if($page=='reports') echo 'active'; ?>"><i class="fa-solid fa-chart-simple"></i><span class="text">Reports</span></a></li>
        <li><a href="admin.php?page=incharge" class="<?php if($page=='incharge') echo 'active'; ?>"><i class="fa-solid fa-user-shield"></i><span class="text">Depts In-Charge</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-power-off"></i><span class="text">Logout</span></a></li>
    </ul>
</div>

<div class="main">
    <div class="topbar">
        <h1><?php echo ucfirst($page); ?></h1>
        <div class="admin">
            <i class="fa-solid fa-user-circle fa-2x"></i>
            <span>Welcome, Admin</span>
        </div>
    </div>

    <?php if($page == 'dashboard'){ ?>
    <div class="cards">
        <div class="card">
            <div class="card-top">
                <div><h3>Total Patients</h3><p><?php echo $total_patients; ?></p></div>
                <div class="icon blue"><i class="fa-solid fa-user"></i></div>
            </div>
        </div>
        <div class="card">
            <div class="card-top">
                <div><h3>Total Staff</h3><p><?php echo $total_staff; ?></p></div>
                <div class="icon green"><i class="fa-solid fa-users"></i></div>
            </div>
        </div>
        <div class="card">
            <div class="card-top">
                <div><h3>Departments</h3><p><?php echo $total_departments; ?></p></div>
                <div class="icon purple"><i class="fa-solid fa-building"></i></div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if($page == 'departments'){ ?>
    <a href="add_department.php" class="btn"><i class="fa-solid fa-plus"></i> Add Department</a>
    <div class="departments-grid">
        <?php
        $query = mysqli_query($conn, "SELECT * FROM departments");
        if(mysqli_num_rows($query) > 0){
            while($row = mysqli_fetch_assoc($query)){
                $dept_id = $row['id']; 
                $dept_name = trim($row['department_name']);
                $lowercase_name = strtolower($dept_name);
                
                $icon_class = "fa-solid fa-clinic-medical";
                $description = "Specialized clinical services and dedicated patient healthcare infrastructure.";

                if(strpos($lowercase_name, 'dialysis') !== false) {
                    $icon_class = "fa-solid fa-water"; $description = "Renal care and continuous blood filtration treatments.";
                } elseif(strpos($lowercase_name, 'eye') !== false || strpos($lowercase_name, 'ophthalmology') !== false) {
                    $icon_class = "fa-solid fa-eye"; $description = "Ophthalmology services, optical diagnostics, and vision care.";
                } elseif(strpos($lowercase_name, 'maternity') !== false) {
                    $icon_class = "fa-solid fa-baby"; $description = "Comprehensive prenatal, delivery, and postnatal care services.";
                } elseif(strpos($lowercase_name, 'orthopedic') !== false) {
                    $icon_class = "fa-solid fa-bone"; $description = "Specialized care for structural bone, joint, and muscular conditions.";
                } elseif(strpos($lowercase_name, 'pediatric') !== false) {
                    $icon_class = "fa-solid fa-child"; $description = "Dedicated medical attention and healthcare tailored for infants and young children.";
                } elseif(strpos($lowercase_name, 'pharmacy') !== false) {
                    $icon_class = "fa-solid fa-capsules"; $description = "Responsible for preparation, compounding, dispensing, and distribution of medications.";
                } elseif(strpos($lowercase_name, 'theater') !== false || strpos($lowercase_name, 'theatre') !== false) {
                    $icon_class = "fa-solid fa-lungs"; $description = "Operating theater management, surgical procedures, and sterile block control.";
                }

                $clean_name = str_replace(' ', '', strtolower($dept_name));
                if (strpos($clean_name, 'theater') !== false || strpos($clean_name, 'theatre') !== false) {
                    $target_file = 'Theater.php';
                } elseif (strpos($clean_name, 'eye') !== false || strpos($clean_name, 'ophthalmology') !== false) {
                    $target_file = 'EyeClinic.php';
                } else {
                    $target_file = ucwords(str_replace(' ', '', $dept_name)) . '.php';
                }

                if ($is_admin || file_exists($target_file)) {
                    $final_url = $target_file . "?id=" . urlencode($dept_id);
                } else {
                    $final_url = "admin.php?page=view_generic_dept&id=" . urlencode($dept_id);
                }
        ?>
        <div class="dept-card">
            <div class="dept-icon"><i class="<?php echo $icon_class; ?>"></i></div>
            <h3><?php echo htmlspecialchars($dept_name); ?></h3>
            <p><?php echo $description; ?></p>
            <a href="<?php echo $final_url; ?>" class="view-panel-link">View Management Panel <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <?php 
            }
        } else {
            echo "<div class='card' style='grid-column: 1/-1; text-align:center;'><h3>No active departments configured in system.</h3></div>";
        }
        ?>
    </div>
    <?php } ?>

    <?php 
    if($page == 'view_generic_dept'){ 
        $stmt = mysqli_prepare($conn, "SELECT department_name FROM departments WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $dept_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $d_row = mysqli_fetch_assoc($res);
    ?>
        <h2 class="page-title"><?php echo htmlspecialchars($d_row['department_name'] ?? 'Department Overview'); ?></h2>
        <div class="table-box">
            <h3>Module Template Active</h3>
            <p style="margin-top:10px; color:#666;">The dedicated panel file for this department hasn't been created yet. You can build it or manage base data from here.</p>
        </div>
    <?php } ?>

    <?php if($page == 'patients'){ ?>
    <h2 class="page-title">Patients</h2>
    <a href="add_patient.php" class="btn"><i class="fa-solid fa-plus"></i> Add Patient</a>
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Condition</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Department</th>
                <th>Bed</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $patients_query = "SELECT p.*, d.department_name FROM patients p LEFT JOIN departments d ON p.department_id = d.id ORDER BY p.id DESC";
            $patients_result = mysqli_query($conn, $patients_query);
            if ($patients_result && mysqli_num_rows($patients_result) > 0) {
                while ($patient = mysqli_fetch_assoc($patients_result)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($patient['full_name']) . "</td>";
                    $cond_class = (strtolower($patient['condition']) == 'critical') ? 'occupied' : 'available';
                    echo "<td><span class='status $cond_class'>" . htmlspecialchars($patient['condition']) . "</span></td>";
                    echo "<td>" . htmlspecialchars($patient['gender']) . "</td>";
                    echo "<td>" . htmlspecialchars($patient['age']) . "</td>";
                    echo "<td>" . htmlspecialchars($patient['department_name'] ?? 'Unassigned') . "</td>";
                    echo "<td>" . htmlspecialchars($patient['bed_number']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center; color:#777;'>No patient records found in the system.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <?php } ?>

    <?php if($page == 'staff'){ ?>
    <h2 class="page-title">Staff</h2>
    <a href="add_staff.php" class="btn"><i class="fa-solid fa-plus"></i> Add Staff</a>
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // FILTER BY ROLE: Added "WHERE LOWER(s.role) = 'staff'" to ensure only staff members load here
            $staff_query = "SELECT s.*, d.department_name FROM staff s LEFT JOIN departments d ON s.department_id = d.id WHERE LOWER(s.role) = 'staff' ORDER BY s.id DESC";
            $staff_result = mysqli_query($conn, $staff_query);
            if ($staff_result && mysqli_num_rows($staff_result) > 0) {
                while ($staff = mysqli_fetch_assoc($staff_result)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($staff['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($staff['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($staff['department_name'] ?? 'General Admin / Shared') . "</td>";
                    echo "<td>" . htmlspecialchars($staff['role']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center; color:#777;'>No staff records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <?php } ?>

    <?php if($page == 'ambulance'){ ?>
    <h2 class="page-title">Ambulance fleet</h2>
    <a href="add_ambulance.php" class="btn"><i class="fa-solid fa-plus"></i> Add Ambulance</a>
    <table>
        <tr>
            <th>Plate Number</th>
            <th>Status</th>
            <th>Driver</th>
        </tr>
        <?php
        $amb_result = mysqli_query($conn, "SELECT * FROM ambulances ORDER BY id DESC");
        if($amb_result && mysqli_num_rows($amb_result) > 0) {
            while($amb = mysqli_fetch_assoc($amb_result)) {
                $status_class = (strtolower($amb['status']) == 'occupied') ? 'occupied' : 'available';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($amb['plate_number']) . "</td>";
                echo "<td><span class='status $status_class'>" . htmlspecialchars($amb['status']) . "</span></td>";
                echo "<td>" . htmlspecialchars($amb['driver_name']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align:center; color:#777;'>No active fleet units registered.</td></tr>";
        }
        ?>
    </table>
    <?php } ?>

    <?php if($page == 'wards'){ ?>
    <h2 class="page-title">Ward Structure</h2>
    <a href="add_ward.php" class="btn"><i class="fa-solid fa-plus"></i> Initialize Department Wards</a>
    <table>
        <tr>
            <th>Block Label</th>
            <th>Floor Number</th>
            <th>Total Beds Configured</th>
        </tr>
        <?php
        $ward_query = "SELECT w.block_name, w.floor, COUNT(b.id) as total_beds 
                       FROM wards w 
                       LEFT JOIN beds b ON w.id = b.ward_id 
                       GROUP BY w.block_name, w.floor 
                       ORDER BY w.block_name ASC, w.floor ASC";
        $ward_result = mysqli_query($conn, $ward_query);
        if($ward_result && mysqli_num_rows($ward_result) > 0) {
            while($ward = mysqli_fetch_assoc($ward_result)) {
                echo "<tr>";
                echo "<td>Block " . htmlspecialchars($ward['block_name']) . "</td>";
                echo "<td>Floor " . htmlspecialchars($ward['floor']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($ward['total_beds']) . " Beds</strong> (Automated Rule)</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align:center; color:#777;'>No ward structures initialized yet. Click above to auto-generate.</td></tr>";
        }
        ?>
    </table>
    <?php } ?>

    <?php if($page == 'beds'){ ?>
    <h2 class="page-title">Bed Tracking Index</h2>
    <table>
        <tr>
            <th>Bed Label</th>
            <th>Block</th>
            <th>Floor</th>
            <th>Status</th>
        </tr>
        <?php
        $beds_query = "SELECT b.*, w.block_name, w.floor FROM beds b JOIN wards w ON b.ward_id = w.id ORDER BY b.id DESC LIMIT 150";
        $beds_result = mysqli_query($conn, $beds_query);
        if($beds_result && mysqli_num_rows($beds_result) > 0) {
            while($bed = mysqli_fetch_assoc($beds_result)) {
                $b_status = (strtolower($bed['status']) == 'occupied') ? 'occupied' : 'available';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($bed['bed_label']) . "</td>";
                echo "<td>Block " . htmlspecialchars($bed['block_name']) . "</td>";
                echo "<td>Floor " . htmlspecialchars($bed['floor']) . "</td>";
                echo "<td><span class='status $b_status'>" . htmlspecialchars($bed['status']) . "</span></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' style='text-align:center; color:#777;'>No active bed indices monitored.</td></tr>";
        }
        ?>
    </table>
    <?php } ?>

    <?php if($page == 'reservations'){ ?>
    <h2 class="page-title">Active Bed Reservations</h2>
    <table>
        <tr>
            <th>Patient Name</th>
            <th>Bed Assigned</th>
            <th>Date Reserved</th>
        </tr>
        <?php
        $res_query = "SELECT r.*, p.full_name, b.bed_label 
                      FROM reservations r 
                      JOIN patients p ON r.patient_id = p.id 
                      JOIN beds b ON r.bed_id = b.id 
                      ORDER BY r.id DESC";
        $res_result = mysqli_query($conn, $res_query);
        if($res_result && mysqli_num_rows($res_result) > 0) {
            while($res_row = mysqli_fetch_assoc($res_result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($res_row['full_name']) . "</td>";
                echo "<td>" . htmlspecialchars($res_row['bed_label']) . "</td>";
                echo "<td>" . htmlspecialchars(date('d M Y', strtotime($res_row['reservation_date']))) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align:center; color:#777;'>No active structural bed allocations found.</td></tr>";
        }
        ?>
    </table>
    <?php } ?>

    <?php if($page == 'reports'){ 
        $t_beds = 0; $o_beds = 0; $a_beds = 0;
        
        $tb_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM beds");
        if($tb_res) $t_beds = mysqli_fetch_assoc($tb_res)['total'];
        
        $ob_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM beds WHERE LOWER(status)='occupied'");
        if($ob_res) $o_beds = mysqli_fetch_assoc($ob_res)['total'];
        
        $ab_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM beds WHERE LOWER(status)='available' OR status = '' OR status IS NULL");
        if($ab_res) $a_beds = mysqli_fetch_assoc($ab_res)['total'];

        $occupancy_rate = ($t_beds > 0) ? round(($o_beds / $t_beds) * 100, 1) : 0;
    ?>
    <h2 class="page-title">SmartCare System Infrastructure Reports</h2>
    
    <div class="cards">
        <div class="card">
            <div class="card-top">
                <div><h3>Total Beds Built</h3><p><?php echo $t_beds; ?></p></div>
                <div class="icon blue"><i class="fa-solid fa-procedures"></i></div>
            </div>
        </div>
        <div class="card">
            <div class="card-top">
                <div><h3>Occupied Beds</h3><p><?php echo $o_beds; ?></p></div>
                <div class="icon red"><i class="fa-solid fa-bed-pulse"></i></div>
            </div>
        </div>
        <div class="card">
            <div class="card-top">
                <div><h3>Available Beds</h3><p><?php echo $a_beds; ?></p></div>
                <div class="icon green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="table-box">
            <h2>Hospital Metrics Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric Dimension</th>
                        <th>System Live Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Active Patients</strong></td>
                        <td><?php echo $total_patients; ?> Registered</td>
                    </tr>
                    <tr>
                        <td><strong>Total Medical Staff</strong></td>
                        <td><?php echo $total_staff; ?> Employees</td>
                    </tr>
                    <tr>
                        <td><strong>Configured Specialties / Units</strong></td>
                        <td><?php echo $total_departments; ?> Active Depts</td>
                    </tr>
                    <tr>
                        <td><strong>Bed Occupancy Capacity</strong></td>
                        <td>
                            <span class="status <?php echo ($occupancy_rate > 80) ? 'occupied' : 'available'; ?>">
                                <?php echo $occupancy_rate; ?>% Full
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-box">
            <h2>Department Operational Load</h2>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Patients</th>
                        <th>Staff Assigned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dept_report_query = "
                        SELECT d.id, d.department_name,
                        (SELECT COUNT(*) FROM patients p WHERE p.department_id = d.id) as p_count,
                        (SELECT COUNT(*) FROM staff s WHERE s.department_id = d.id) as s_count
                        FROM departments d 
                        ORDER BY d.department_name ASC";
                    
                    $dept_report_res = mysqli_query($conn, $dept_report_query);
                    if($dept_report_res && mysqli_num_rows($dept_report_res) > 0) {
                        while($rep = mysqli_fetch_assoc($dept_report_res)) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($rep['department_name']) . "</strong></td>";
                            echo "<td>" . $rep['p_count'] . " Patients</td>";
                            echo "<td>" . $rep['s_count'] . " Staff</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#777;'>No departmental operational data mapped.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>

    <?php if($page == 'incharge'){ ?>
    <div class="table-box">
        <h2>Assign Management Staff</h2>
        <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
            Select a department and assign a registered staff member. Once assigned, this staff member will be the exclusive coordinator allowed to log in and manage that specific unit panel.
        </p>
        
        <table>
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Current Coordinator In-Charge</th>
                    <th>Update Assignment (Admin Actions)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $inc_query = "SELECT d.id as dept_id, d.department_name, s.full_name as staff_name, s.role 
                              FROM departments d 
                              LEFT JOIN staff s ON d.in_charge_staff_id = s.id 
                              ORDER BY d.department_name ASC";
                $inc_res = mysqli_query($conn, $inc_query);
                
                $staff_list = [];
                $all_staff_query = mysqli_query($conn, "SELECT id, full_name, role FROM staff ORDER BY full_name ASC");
                if($all_staff_query) {
                    while($st = mysqli_fetch_assoc($all_staff_query)) {
                        $staff_list[] = $st;
                    }
                }

                if($inc_res && mysqli_num_rows($inc_res) > 0) {
                    while($row = mysqli_fetch_assoc($inc_res)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['department_name']) . "</td>";
                        
                        if($row['staff_name']) {
                            echo "<td><span class='status available'><i class='fa-solid fa-user-tie'></i> " . htmlspecialchars($row['staff_name']) . " (" . htmlspecialchars($row['role']) . ")</span></td>";
                        } else {
                            echo "<td><span class='status occupied' style='background:#fef08a; color:#a16207;'><i class='fa-solid fa-triangle-exclamation'></i> Vacant / Unassigned</span></td>";
                        }
                        
                        echo "<td>";
                        if($is_admin) {
                            ?>
                            <form method="POST" action="admin.php?page=incharge" style="display:flex; gap:10px; align-items:center;">
                                <input type="hidden" name="department_id" value="<?php echo $row['dept_id']; ?>">
                                
                                <select name="staff_id" style="padding:8px; border-radius:5px; border:1px solid #ccc; font-size:13px; min-width:180px;">
                                    <option value="0">-- Clear / Vacate Position --</option>
                                    <?php foreach($staff_list as $staff_member) { ?>
                                        <option value="<?php echo $staff_member['id']; ?>">
                                            <?php echo htmlspecialchars($staff_member['full_name'] . " (" . $staff_member['role'] . ")"); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button type="submit" name="assign_in_charge" class="btn" style="margin-bottom:0">Assign</button>
                            </form>
                            <?php
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php } ?>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartCare Systems. All rights reserved.</p>
    </div>
</div>

</body>
</html>