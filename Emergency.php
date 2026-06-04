<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ER & Trauma Management - Medicare OS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #fdfdfd;
            --sidebar-bg: #ffffff;
            --primary-orange: #e65100; 
            --primary-gradient: linear-gradient(135deg, #d84315 0%, #ff8f00 100%);
            --light-orange: #fff3e0;
            --text-main: #212121;
            --text-muted: #757575;
            --border-color: #eeeeee;
            --white: #ffffff;
            --triage-red: #d32f2f;
            --triage-yellow: #fbc02d;
            --triage-green: #388e3c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

        .top-navbar { 
            display: flex; justify-content: space-between; align-items: center; padding: 0 40px; min-height: 85px; 
            background: var(--primary-gradient); color: var(--white); box-shadow: 0 4px 15px rgba(216, 67, 21, 0.2); z-index: 1001;
        }

        .dashboard-container { display: flex; flex: 1; height: calc(100vh - 85px); overflow: hidden; }

        .sidebar { width: 260px; background-color: var(--sidebar-bg); border-right: 2px solid var(--border-color); display: flex; flex-direction: column; padding: 30px 20px; }
        .logo-area { display: flex; align-items: center; gap: 12px; font-size: 1.3rem; font-weight: 800; color: var(--primary-orange); margin-bottom: 30px; }
        
        .nav-list { list-style: none; flex-grow: 1; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; margin-bottom: 5px; border-radius: 10px; color: var(--text-muted); cursor: pointer; transition: 0.2s; font-weight: 600; }
        .nav-item:hover { background-color: var(--light-orange); color: var(--primary-orange); }
        .nav-item.active { background-color: var(--primary-orange) !important; color: var(--white) !important; box-shadow: 0 4px 12px rgba(230, 81, 0, 0.3); }

        .main-wrapper { flex: 1; padding: 35px 45px; overflow-y: auto; }
        .tab-content { display: none; animation: fadeIn 0.2s ease-in-out; }
        .tab-content.active-section { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: var(--white); border: 1px solid var(--border-color); padding: 22px; border-radius: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; background: var(--light-orange); color: var(--primary-orange); }

        .table-section { background: var(--white); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text-muted); font-size: 0.8rem; padding: 15px; background: #fafafa; border-bottom: 2px solid var(--border-color); }
        td { padding: 18px 15px; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); }

        .btn-add-data { background: #1b5e20; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 10px; }
        .triage-pill { padding: 6px 14px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: white; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; width: 450px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.8rem; margin-bottom: 4px; font-weight: 700; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-save { background: var(--primary-orange); color: white; border: none; width: 100%; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

    <nav class="top-navbar">
        <div class="welcome-text">Emergency Dept | <span>Triage Lead</span></div>
        <div class="hospital-identity"><i class="fas fa-ambulance"></i> Medicare OS Emergency</div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-area"><i class="fas fa-bolt"></i> <span>ER-RESPONSE</span></div>
            <ul class="nav-list">
                <li class="nav-item active" data-tab="er-dash" data-title="ER Live Status"><i class="fas fa-tachometer-alt"></i> Dashboard</li>
                <li class="nav-item" data-tab="triage" data-title="Active Triage Queue"><i class="fas fa-notes-medical"></i> Triage Queue</li>
                <li class="nav-item" data-tab="ambulance" data-title="Ambulance Tracking"><i class="fas fa-shipping-fast"></i> Fleet/Dispatch</li>
                <li class="nav-item" data-tab="trauma" data-title="Trauma Bay Status"><i class="fas fa-procedures"></i> Trauma Bays</li>
                <li class="nav-item" data-tab="blood" data-title="Blood Bank Emergency Stock"><i class="fas fa-tint"></i> Blood Bank</li>
                <li class="nav-item" data-tab="staff" data-title="Emergency Personnel"><i class="fas fa-user-md"></i> Staff</li>
                <li class="nav-item" data-tab="duty" data-title="ER On-Call Roster"><i class="fas fa-clock"></i> Duty</li>
            </ul>
        </aside>

        <main class="main-wrapper">
            <header><h2 id="page-title" style="margin-bottom: 25px;">ER Live Status</h2></header>

            <section id="er-dash" class="tab-content active-section">
                <button onclick="openModal('addAlertModal')" class="btn-add-data"><i class="fas fa-exclamation-circle"></i> Dispatch Alert</button>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div><h4>In Triage</h4><p>12 Patients</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="color:var(--triage-red)"><i class="fas fa-heartbeat"></i></div><div><h4>Code Red</h4><p>2 Cases</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fas fa-truck-loading"></i></div><div><h4>En Route</h4><p>3 Ambulances</p></div></div>
                </div>
                <div class="table-section">
                    <h3>Critical Updates</h3>
                    <ul id="alertList" style="list-style: none; color: #b71c1c; font-weight: 600;">
                        <li><i class="fas fa-exclamation-triangle"></i> Incoming: Multiple MVA casualty, 5 min ETA.</li>
                    </ul>
                </div>
            </section>

            <section id="triage" class="tab-content">
                <button onclick="openModal('addTriageModal')" class="btn-add-data"><i class="fas fa-plus"></i> New Triage Entry</button>
                <div class="table-section">
                    <table id="triageTable">
                        <thead><tr><th>Patient</th><th>Arrival</th><th>Complaint</th><th>Priority</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Unknown Male</strong></td><td>00:15</td><td>Trauma/Head Injury</td><td><span class="triage-pill" style="background:var(--triage-red)">Level 1 (Immediate)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="ambulance" class="tab-content">
                <button onclick="openModal('addAmbulanceModal')" class="btn-add-data"><i class="fas fa-plus"></i> Dispatch Unit</button>
                <div class="table-section">
                    <table id="ambTable">
                        <thead><tr><th>Unit ID</th><th>Location</th><th>Status</th><th>ETA</th></tr></thead>
                        <tbody>
                            <tr><td>AMB-09</td><td>Kilimani Area</td><td>Returning</td><td>8 Mins</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="trauma" class="tab-content">
                <button onclick="openModal('addBayModal')" class="btn-add-data"><i class="fas fa-bed"></i> Assign Bay</button>
                <div class="table-section">
                    <table id="bayTable">
                        <thead><tr><th>Bay #</th><th>Patient</th><th>Lead Doctor</th><th>Condition</th></tr></thead>
                        <tbody>
                            <tr><td>Bay A1</td><td><strong>Jane Doe</strong></td><td>Dr. Kiptoo</td><td>Critical</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="blood" class="tab-content">
                <button onclick="openModal('addBloodModal')" class="btn-add-data"><i class="fas fa-plus"></i> Update Inventory</button>
                <div class="table-section">
                    <table id="bloodTable">
                        <thead><tr><th>Type</th><th>Units Available</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr><td>O Negative</td><td>4 Units</td><td><span class="triage-pill" style="background:var(--triage-red)">Critical Low</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="staff" class="tab-content">
                <button onclick="openModal('addStaffModal')" class="btn-add-data"><i class="fas fa-user-plus"></i> Register Staff</button>
                <div class="table-section">
                    <table id="staffTable">
                        <thead><tr><th>Name</th><th>Role</th><th>Designation</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section id="duty" class="tab-content">
                <button onclick="openModal('addDutyModal')" class="btn-add-data"><i class="fas fa-calendar-alt"></i> Assign Shift</button>
                <div class="table-section">
                    <table id="dutyTable">
                        <thead><tr><th>Day</th><th>ER Lead</th><th>Senior Nurse</th></tr></thead>
                        <tbody>
                            <tr><td>Tonight</td><td id="Mon-1">-</td><td id="Mon-2">-</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="addTriageModal">
        <div class="modal-content">
            <h3>Emergency Triage</h3>
            <div class="form-group"><label>Patient Name/ID</label><input type="text" id="tName"></div>
            <div class="form-group"><label>Main Complaint</label><input type="text" id="tComp"></div>
            <div class="form-group"><label>Priority Level</label>
                <select id="tLevel">
                    <option value="Level 1" style="color:red">Level 1 (Immediate)</option>
                    <option value="Level 2" style="color:orange">Level 2 (Urgent)</option>
                    <option value="Level 3" style="color:green">Level 3 (Non-Urgent)</option>
                </select>
            </div>
            <button class="btn-save" onclick="saveTriage()">Admit to Queue</button>
            <button onclick="closeModal('addTriageModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addAmbulanceModal">
        <div class="modal-content">
            <h3>Dispatch Ambulance</h3>
            <div class="form-group"><label>Unit ID</label><input type="text" id="aID"></div>
            <div class="form-group"><label>Destination/Pickup</label><input type="text" id="aLoc"></div>
            <div class="form-group"><label>ETA</label><input type="text" id="aEta"></div>
            <button class="btn-save" onclick="saveAmbulance()">Dispatch Now</button>
            <button onclick="closeModal('addAmbulanceModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addBayModal">
        <div class="modal-content">
            <h3>Bay Assignment</h3>
            <div class="form-group"><label>Bay #</label><input type="text" id="bNo"></div>
            <div class="form-group"><label>Patient</label><input type="text" id="bPat"></div>
            <div class="form-group"><label>Doctor</label><input type="text" id="bDoc"></div>
            <button class="btn-save" onclick="saveBay()">Assign Bay</button>
            <button onclick="closeModal('addBayModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addBloodModal">
        <div class="modal-content">
            <h3>Update Blood Stock</h3>
            <div class="form-group"><label>Blood Type</label><input type="text" id="blType"></div>
            <div class="form-group"><label>Units Added</label><input type="number" id="blQty"></div>
            <button class="btn-save" onclick="saveBlood()">Update Bank</button>
            <button onclick="closeModal('addBloodModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addStaffModal">
        <div class="modal-content">
            <h3>Register Staff</h3>
            <div class="form-group"><label>Name</label><input type="text" id="stName"></div>
            <div class="form-group"><label>Role</label><select id="stRole"><option>Trauma Surgeon</option><option>ER Nurse</option><option>Paramedic</option></select></div>
            <button class="btn-save" onclick="saveStaff()">Register</button>
            <button onclick="closeModal('addStaffModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addAlertModal">
        <div class="modal-content">
            <h3>Emergency Alert</h3>
            <div class="form-group"><label>Alert Message</label><input type="text" id="alMsg"></div>
            <button class="btn-save" onclick="saveAlert()">Broadcast Alert</button>
            <button onclick="closeModal('addAlertModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <div class="modal-overlay" id="addDutyModal">
        <div class="modal-content">
            <h3>On-Call Shift</h3>
            <div class="form-group"><label>ER Lead Doctor</label><input type="text" id="dLead"></div>
            <div class="form-group"><label>Charge Nurse</label><input type="text" id="dNurse"></div>
            <button class="btn-save" onclick="saveDuty()">Assign</button>
            <button onclick="closeModal('addDutyModal')" style="width:100%; border:none; background:none; margin-top:10px; cursor:pointer;">Cancel</button>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function saveTriage() {
            const level = document.getElementById('tLevel').value;
            const color = level.includes('1') ? 'var(--triage-red)' : level.includes('2') ? 'var(--triage-yellow)' : 'var(--triage-green)';
            const row = `<tr><td><strong>${document.getElementById('tName').value}</strong></td><td>Just Now</td><td>${document.getElementById('tComp').value}</td><td><span class="triage-pill" style="background:${color}">${level}</span></td></tr>`;
            document.querySelector('#triageTable tbody').insertAdjacentHTML('afterbegin', row);
            closeModal('addTriageModal');
        }

        function saveAmbulance() {
            const row = `<tr><td>${document.getElementById('aID').value}</td><td>${document.getElementById('aLoc').value}</td><td>En Route</td><td>${document.getElementById('aEta').value}</td></tr>`;
            document.querySelector('#ambTable tbody').insertAdjacentHTML('beforeend', row);
            closeModal('addAmbulanceModal');
        }

        function saveBay() {
            const row = `<tr><td>${document.getElementById('bNo').value}</td><td><strong>${document.getElementById('bPat').value}</strong></td><td>${document.getElementById('bDoc').value}</td><td>Occupied</td></tr>`;
            document.querySelector('#bayTable tbody').insertAdjacentHTML('beforeend', row);
            closeModal('addBayModal');
        }

        function saveBlood() {
            const row = `<tr><td>${document.getElementById('blType').value}</td><td>${document.getElementById('blQty').value} Units</td><td><span class="triage-pill" style="background:var(--triage-green)">Updated</span></td></tr>`;
            document.querySelector('#bloodTable tbody').insertAdjacentHTML('afterbegin', row);
            closeModal('addBloodModal');
        }

        function saveAlert() {
            const li = `<li><i class="fas fa-exclamation-triangle"></i> ${document.getElementById('alMsg').value}</li>`;
            document.getElementById('alertList').insertAdjacentHTML('afterbegin', li);
            closeModal('addAlertModal');
        }

        function saveStaff() {
            const row = `<tr><td><strong>${document.getElementById('stName').value}</strong></td><td>${document.getElementById('stRole').value}</td><td>ER Team</td></tr>`;
            document.querySelector('#staffTable tbody').insertAdjacentHTML('beforeend', row);
            closeModal('addStaffModal');
        }

        function saveDuty() {
            document.getElementById('Mon-1').innerText = document.getElementById('dLead').value;
            document.getElementById('Mon-2').innerText = document.getElementById('dNurse').value;
            closeModal('addDutyModal');
        }

        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                const target = item.getAttribute('data-tab');
                document.getElementById('page-title').innerText = item.getAttribute('data-title');
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active-section'));
                document.getElementById(target).classList.add('active-section');
            });
        });
    </script>
</body>
</html>