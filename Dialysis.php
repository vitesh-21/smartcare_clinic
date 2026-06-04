<?php
include 'db.php';
session_start();

// 1. Check if a user session even exists
if (!isset($_SESSION['role'])) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:100px;'>
            <h2 style='color:#dc3545;'>Access Denied</h2>
            <p>Please log into your account to access Medicare OS.</p>
            <a href='index.php' style='color:#0277bd; font-weight:600;'>Return to Login</a>
         </div>");
}

// 2. Extract session variables
$user_role = $_SESSION['role'];
$logged_in_staff_id = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;

// 3. Define the database identity for this specific page (Dialysis department)
$department_name = "Dialysis"; 

// 4. Fetch the ID of the coordinator currently assigned to this department
$stmt = mysqli_prepare($conn, "SELECT in_charge_staff_id FROM departments WHERE department_name = ?");
mysqli_stmt_bind_param($stmt, "s", $department_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$department = mysqli_fetch_assoc($result);

$assigned_in_charge_id = $department ? intval($department['in_charge_staff_id']) : null;

// Use a JOIN to filter by department name while selecting staff data
$staff_query = "SELECT s.full_name, s.role 
                FROM staff s 
                JOIN departments d ON s.department_id = d.id 
                WHERE d.department_name = 'Dialysis'";

$staff_result = mysqli_query($conn, $staff_query);

if (!$staff_result) {
    die("Database Error: " . mysqli_error($conn));
}

// 6. SMART SECURITY GATE
$is_authorized_admin = ($user_role === 'admin');
$is_authorized_in_charge = ($assigned_in_charge_id !== null && $logged_in_staff_id === $assigned_in_charge_id);

if (!$is_authorized_admin && !$is_authorized_in_charge) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:100px; padding:20px;'>
            <div style='font-size:50px; color:#dc3545;'><i class='fas fa-exclamation-triangle'></i></div>
            <h2 style='color:#dc3545; margin-top:10px;'>Restricted Access Area</h2>
            <p style='color:#6c757d; max-width:500px; margin:10px auto;'>
                You do not have administrative clearance or an active assignment coordinator token to manage the <strong>" . htmlspecialchars($department_name) . " Unit</strong>.
            </p>
            <a href='admin.php?page=departments' style='display:inline-block; margin-top:15px; padding:10px 20px; background:#0277bd; color:white; border-radius:8px; text-decoration:none; font-weight:600;'>Back to home</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dialysis Management System - Medicare OS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f4f7f9;
            --sidebar-bg: #ffffff;
            --primary-blue: #0277bd; 
            --primary-gradient: linear-gradient(135deg, #0288d1 0%, #26c6da 100%);
            --light-blue: #e1f5fe;
            --text-main: #2c3e50;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --white: #ffffff;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; min-height: 85px; background: var(--primary-gradient); color: var(--white); box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1001; }
        .welcome-text span { color: var(--white); font-weight: 700; font-size: 1.4rem; }
        .hospital-identity { font-size: 1.1rem; font-weight: 700; background: rgba(255, 255, 255, 0.15); padding: 12px 25px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; gap: 12px; }

        .dashboard-container { display: flex; flex: 1; height: calc(100vh - 85px); overflow: hidden; }

        .sidebar { width: 260px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; z-index: 100; }
        .logo-area { display: flex; align-items: center; gap: 12px; font-size: 1.3rem; font-weight: 800; color: var(--primary-blue); margin-bottom: 30px; flex-shrink: 0; }
        
        .nav-list { list-style: none; flex-grow: 1; overflow-y: auto; padding-right: 5px; }
        .nav-list::-webkit-scrollbar { width: 4px; }
        .nav-list::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 18px; margin-bottom: 5px; border-radius: 10px; color: var(--text-muted); cursor: pointer; transition: 0.2s; font-weight: 500; }
        .nav-item:hover { background-color: var(--light-blue); color: var(--primary-blue); }
        .nav-item.active { background-color: var(--primary-blue) !important; color: var(--white) !important; box-shadow: 0 4px 12px rgba(2, 119, 189, 0.3); }

        .logout-area { border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: auto; flex-shrink: 0; }
        .btn-logout { display: flex; align-items: center; gap: 15px; padding: 14px 18px; width: 100%; background: none; border: none; border-radius: 10px; color: var(--danger); cursor: pointer; font-weight: 600; transition: 0.2s; font-size: 1rem; }
        .btn-logout:hover { background-color: #fff5f5; }

        .main-wrapper { flex: 1; padding: 35px 45px; overflow-y: auto; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active-section { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: var(--white); border: 1px solid var(--border-color); padding: 22px; border-radius: 16px; display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; background: var(--light-blue); color: var(--primary-blue); }

        .table-section { background: var(--white); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-muted); font-size: 0.8rem; padding: 15px; background: #fafbfc; border-bottom: 2px solid var(--border-color); text-transform: uppercase; }
        td { padding: 18px 15px; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); }

        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: #e8f5e9; color: var(--success); }
        .status-session { background: #fff3e0; color: var(--warning); }
        .status-offline { background: #f5f5f5; color: var(--text-muted); }
        
        .btn-action { background: var(--primary-blue); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-danger { background: var(--danger); }
        .btn-admit { background: var(--success); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; width: 450px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; }

        .morning-text { color: #f39c12; }
        .night-text { color: #5c6bc0; }
    </style>
</head>
<body>

    <nav class="top-navbar">
        <div class="welcome-text">Renal Unit Dashboard | <span><?php echo ($user_role === 'admin') ? 'Master Admin' : (isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Coordinator Panel'); ?></span></div>
        <div class="hospital-identity"><i class="fas fa-tint"></i> Medicare OS Dialysis</div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-area"><i class="fas fa-hand-holding-heart"></i> <span>MEDICARE OS</span></div>
            <ul class="nav-list">
                <li class="nav-item active" data-tab="dialysis-dash" data-title="Unit Overview"><i class="fas fa-chart-line"></i> Dashboard</li>
                <li class="nav-item" data-tab="sessions" data-title="Live Dialysis Sessions"><i class="fas fa-wave-square"></i> Active Sessions</li>
                <li class="nav-item" data-tab="machine-monitor" data-title="Dialysis Machine Monitor"><i class="fas fa-microchip"></i> Machine Monitor</li>
                <li class="nav-item" data-tab="patients" data-title="Patient Registry"><i class="fas fa-users"></i> Patient Database</li>
                <li class="nav-item" data-tab="discharge" data-title="Session Completions"><i class="fas fa-check-circle"></i> Completed</li>
                <li class="nav-item" data-tab="staff" data-title="Nephrology Team"><i class="fas fa-user-md"></i> Medical Staff</li>
                <li class="nav-item" data-tab="duty" data-title="Weekly Nurse Shift Roster"><i class="fas fa-clock"></i> Duty</li>
            </ul>

            <div class="logout-area">
                <a href="admin.php?page=departments" style="display: flex; align-items: center; gap: 15px; padding: 14px 18px; width: 100%; border-radius: 10px; color: #475569; text-decoration: none; font-weight: 600; margin-bottom: 5px; font-size: 1rem; transition: 0.2s;"><i class="fas fa-arrow-left"></i> Central Panel</a>
                <button class="btn-logout" onclick="handleLogout()"><i class="fas fa-power-off"></i> <span>Log Out</span></button>
            </div>
        </aside>

        <main class="main-wrapper">
            <header><h2 id="page-title" style="margin-bottom: 25px;">Unit Overview</h2></header>

            <section id="dialysis-dash" class="tab-content active-section">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-tint"></i></div><div><h4>Active Sessions</h4><p id="stat-active">0</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-plug"></i></div><div><h4>Machines Ready</h4><p>12</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-injured"></i></div><div><h4>Patients Today</h4><p>18</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div><h4>Total Revenue</h4><p>UGX <span id="revenue-display">0</span></p></div></div>
                </div>
                <div class="table-section">
                    <h3>Urgent Notifications</h3>
                    <p style="color: var(--text-muted)">Machine #04 requires maintenance calibration. | Patient "John Smith" due for Hep-B screening.</p>
                </div>
            </section>

            <section id="sessions" class="tab-content">
                <button onclick="openModal('newSessionModal')" class="btn-action btn-admit">
                    <i class="fas fa-user-plus"></i> Admit Patient
                </button>
                <div class="table-section">
                    <h3>Active Dialysis Monitor</h3>
                    <table id="sessionTable">
                        <thead><tr><th>Machine ID</th><th>Patient Name</th><th>Access Type</th><th>Time Remaining</th><th>Action</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section id="discharge" class="tab-content">
                <div class="table-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Discharged Patients (Today)</h3>
                    </div>
                    <table id="completedTable">
                        <thead><tr><th>Patient</th><th>Duration</th><th>UF Goal Reached</th><th>Time</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button onclick="exportTableToCSV('dialysis_records.csv')" class="btn-action" style="background:var(--success);">
                    <i class="fas fa-file-download"></i> Export Records
                </button>
            </section>

            <section id="machine-monitor" class="tab-content">
                <div class="table-section">
                    <h3>Hemodialysis Equipment Status</h3>
                    <table>
                        <thead><tr><th>Machine ID</th><th>Model</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr><td>M-01</td><td>Fresenius 5008</td><td><span class="status-pill status-active">Available</span></td></tr>
                            <tr><td>M-02</td><td>Fresenius 5008</td><td><span class="status-pill status-active">Available</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="patients" class="tab-content">
                <div class="table-section">
                    <h3>Chronic Kidney Disease (CKD) Registry</h3>
                    <table id="patientRegistryTable">
                        <thead><tr><th>Patient ID</th><th>Name</th><th>Vascular Access</th><th>Status</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section id="staff" class="tab-content">
                <div class="table-section">
                    <h3>Renal Team Directory</h3>
                    <table>
                        <thead><tr><th>Name</th><th>Role</th></tr></thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($staff_result)) { ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="duty" class="tab-content">
                <button onclick="openModal('addDutyModal')" class="btn-action btn-admit">
                    <i class="fas fa-calendar-plus"></i> Add Duty
                </button>
                <div class="table-section">
                    <table id="dutyTable">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th><i class="fas fa-sun morning-text"></i> Morning Shift (06-18)</th>
                                <th><i class="fas fa-moon night-text"></i> Night Shift (18-06)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Monday</strong></td><td id="Mon-M">Nurse Sarah J.</td><td id="Mon-N">Nurse Mike T.</td></tr>
                            <tr><td><strong>Tuesday</strong></td><td id="Tue-M">Nurse Alice W.</td><td id="Tue-N">Nurse Kevin O.</td></tr>
                            <tr><td><strong>Wednesday</strong></td><td id="Wed-M">Nurse Sarah J.</td><td id="Wed-N">Nurse Lucy W.</td></tr>
                            <tr><td><strong>Thursday</strong></td><td id="Thu-M">Nurse Kevin O.</td><td id="Thu-N">Nurse Mike T.</td></tr>
                            <tr><td><strong>Friday</strong></td><td id="Fri-M">Nurse Alice W.</td><td id="Fri-N">Nurse Lucy W.</td></tr>
                            <tr><td><strong>Saturday</strong></td><td id="Sat-M">Nurse Sarah J.</td><td id="Sat-N">Nurse Mike T.</td></tr>
                            <tr><td><strong>Sunday</strong></td><td id="Sun-M">Nurse Kevin O.</td><td id="Sun-N">Nurse Alice W.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="newSessionModal">
        <div class="modal-content">
            <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Admit & Initiate Session</h3>
            <div class="form-group"><label>Patient Name</label><input type="text" id="sNameInput"></div>
            <div class="form-group"><label>Age</label><input type="number" id="sAgeInput"></div>
            <div class="form-group"><label>Gender</label><select id="sGenderInput"><option>Male</option><option>Female</option><option>Other</option></select></div>
            <div class="form-group"><label>Assign Machine</label><select id="sMachineInput"><option>M-01</option><option>M-02</option><option>M-03</option><option>M-04</option></select></div>
            <div class="form-group"><label>Vascular Access</label><select id="sAccessInput"><option>AV Fistula</option><option>AV Graft</option><option>Central Catheter</option></select></div>
            <div class="form-group">
                <label>Shift Selection</label>
                <select id="sShiftInput">
                    <option value="Morning">Morning (06:00 - 18:00)</option>
                    <option value="Night">Night (18:00 - 06:00)</option>
                </select>
            </div>
            <button class="btn-action" style="width:100%" onclick="admitPatient()">Connect Patient</button>
            <button onclick="closeModal('newSessionModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer; color: var(--text-muted);">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addDutyModal">
        <div class="modal-content">
            <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Assign Nurse Duty</h3>
            <div class="form-group"><label>Select Day</label><select id="dutyDay"><option value="Mon">Monday</option><option value="Tue">Tuesday</option><option value="Wed">Wednesday</option><option value="Thu">Thursday</option><option value="Fri">Friday</option><option value="Sat">Saturday</option><option value="Sun">Sunday</option></select></div>
            <div class="form-group"><label>Shift Type</label><select id="dutyShift"><option value="M">Morning Shift</option><option value="N">Night Shift</option></select></div>
            <div class="form-group"><label>Nurse Name</label><input type="text" id="dutyNurseName"></div>
            <button class="btn-action" style="width:100%" onclick="updateDuty()">Update Schedule</button>
            <button onclick="closeModal('addDutyModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer; color: var(--text-muted);">Cancel</button>
        </div>
    </div>

    <script>
        let currentPatientCount = 0;
        let totalRevenue = 0;
        let morningCount = 0;
        let nightCount = 0;
        const MAX_PER_SHIFT = 5;

        function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        function handleLogout() { window.location.href = 'logout.php'; }

        function admitPatient() {
            const name = document.getElementById('sNameInput').value;
            const access = document.getElementById('sAccessInput').value;
            const machine = document.getElementById('sMachineInput').value;
            const shift = document.getElementById('sShiftInput').value;
            
            // Capacity Check
            if (shift === 'Morning' && morningCount >= MAX_PER_SHIFT) {
                alert("Morning shift capacity reached (5/5).");
                return;
            } else if (shift === 'Night' && nightCount >= MAX_PER_SHIFT) {
                alert("Night shift capacity reached (5/5).");
                return;
            }

            const rowId = "row-" + Date.now();
            
            document.querySelector('#sessionTable tbody').insertAdjacentHTML('beforeend', `
                <tr id="${rowId}">
                    <td>${machine}</td>
                    <td><strong>${name}</strong></td>
                    <td>${access}</td>
                    <td class="timer" style="color:var(--primary-blue)">02:00:00</td>
                    <td><button class="btn-action btn-danger" onclick="dischargePatient('${rowId}', '${name}', '${shift}')">Discharge</button></td>
                </tr>`);

            document.querySelector('#patientRegistryTable tbody').insertAdjacentHTML('beforeend', `
                <tr id="reg-${rowId}">
                    <td>#${Date.now().toString().slice(-6)}</td>
                    <td><strong>${name}</strong></td>
                    <td>${access}</td>
                    <td><span class="status-pill status-session">Admitted</span></td>
                </tr>`);

            // Increment counters
            if (shift === 'Morning') morningCount++;
            else nightCount++;

            currentPatientCount++;
            document.getElementById('stat-active').innerText = currentPatientCount;
            startTimer(7200, rowId);
            closeModal('newSessionModal');
        }

        function startTimer(duration, rowId) {
            let timer = duration;
            let row = document.getElementById(rowId);
            if(!row) return;
            let display = row.querySelector('.timer');
            let interval = setInterval(() => {
                let h = Math.floor(timer / 3600), m = Math.floor((timer % 3600) / 60), s = timer % 60;
                display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                if (--timer < 0) { clearInterval(interval); display.textContent = "EXPIRED"; }
            }, 1000);
        }

        function dischargePatient(rowId, name, shift) {
            document.getElementById(rowId).remove();
            const regRow = document.getElementById('reg-' + rowId);
            if (regRow) regRow.querySelector('td:last-child').innerHTML = "<span class='status-pill status-offline'>Discharged</span>";
            
            // Decrement shift counters
            if (shift === 'Morning') morningCount--;
            else nightCount--;
            
            totalRevenue += 5000;
            currentPatientCount--;
            document.getElementById('stat-active').innerText = currentPatientCount;
            document.getElementById('revenue-display').innerText = totalRevenue.toLocaleString();
            
            document.querySelector('#completedTable tbody').insertAdjacentHTML('beforeend', `
                <tr><td>${name}</td><td>Completed</td><td>5,000</td><td>${new Date().toLocaleTimeString()}</td></tr>`);
        }

        function updateDuty() {
            const day = document.getElementById('dutyDay').value;
            const shift = document.getElementById('dutyShift').value;
            const nurse = document.getElementById('dutyNurseName').value;
            document.getElementById(`${day}-${shift}`).innerHTML = `<i class="fas fa-user-nurse"></i> ${nurse}`;
            closeModal('addDutyModal');
        }

        function exportTableToCSV(filename) {
            let csv = [];
            let rows = document.querySelectorAll("#completedTable tr");
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) row.push(cols[j].innerText);
                csv.push(row.join(","));
            }
            let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            let downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }

        // Tab Switching Logic
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active-section'));
                const target = this.getAttribute('data-tab');
                document.getElementById(target).classList.add('active-section');
                document.getElementById('page-title').innerText = this.getAttribute('data-title');
            });
        });
    </script>
</body>
</html>