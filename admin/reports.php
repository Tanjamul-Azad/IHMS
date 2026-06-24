<?php
session_start();
require_once '../dbConnect.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$stats = [
    'appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
    'patients' => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
    'revenue' => $pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments")->fetchColumn()
];

?>
<?php
$page = ['role'=>'admin','active'=>'reports','title'=>'Reports & Analytics','subtitle'=>'System metrics and trends','user'=>$_SESSION['user'],'bootstrap'=>true];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Appointments</h5>
                        <p class="card-text fs-3"><?php echo $stats['appointments']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Patients</h5>
                        <p class="card-text fs-3"><?php echo $stats['patients']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Doctors</h5>
                        <p class="card-text fs-3"><?php echo $stats['doctors']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Revenue</h5>
                        <p class="card-text fs-3">$<?php echo number_format($stats['revenue'],2); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Appointments Over Time</div>
                    <div class="card-body">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Patients by Month</div>
                    <div class="card-body">
                        <canvas id="patientsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="alert alert-info mt-4">More analytics and export features coming soon!</div>
    <script>
    // Placeholder chart data
    const ctx1 = document.getElementById('appointmentsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Appointments',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true
            }]
        }
    });
    const ctx2 = document.getElementById('patientsChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Patients',
                data: [5, 9, 7, 8, 6, 4],
                backgroundColor: 'rgba(75, 192, 192, 0.7)'
            }]
        }
    });
    </script>
<?php dash_foot($page); ?> 