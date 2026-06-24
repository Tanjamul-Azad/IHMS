<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$payments = []; $paid = 0; $pending = 0;
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $pid = $stmt->fetchColumn();
    if ($pid) {
        $stmt = $pdo->prepare("SELECT pay.*, a.appointment_date, d.specialization, u.first_name, u.last_name, d.id doctor_id
                               FROM payments pay
                               LEFT JOIN appointments a ON pay.appointment_id=a.id
                               LEFT JOIN doctors d ON a.doctor_id=d.id
                               LEFT JOIN users u ON d.user_id=u.id
                               WHERE pay.patient_id=? ORDER BY pay.payment_date DESC");
        $stmt->execute([$pid]); $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($payments as $p) { if ($p['status']==='completed') $paid += $p['amount']; elseif ($p['status']==='pending') $pending += $p['amount']; }
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'patient','active'=>'payments','title'=>'Payments',
         'subtitle'=>'Your billing history and transactions','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-green"><i data-lucide="check-circle"></i></div></div><div class="kpi__val">$<?= number_format($paid) ?></div><div class="kpi__label">Total paid</div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-amber"><i data-lucide="clock"></i></div></div><div class="kpi__val">$<?= number_format($pending) ?></div><div class="kpi__label">Pending</div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-brand"><i data-lucide="receipt"></i></div></div><div class="kpi__val"><?= count($payments) ?></div><div class="kpi__label">Transactions</div></div>
</div>

<div class="panel">
  <div class="panel__head"><div><h3>Transaction history</h3></div></div>
  <div class="panel__body flush">
    <?php if ($payments): ?>
    <table class="dtable">
      <thead><tr><th>Transaction</th><th>For</th><th>Method</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><b style="font-family:var(--font-head)">#<?= $e($p['transaction_id'] ?: $p['id']) ?></b></td>
          <td><?= $p['first_name'] ? 'Dr. '.$e($p['first_name'].' '.$p['last_name']).' · '.$e($p['specialization']) : 'Consultation' ?></td>
          <td><?= $e($p['payment_method'] ?: '—') ?></td>
          <td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td>
          <td><b style="font-family:var(--font-head)">$<?= number_format($p['amount'],2) ?></b></td>
          <td><span class="pill is-<?= $e($p['status']) ?>"><?= $e($p['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="receipt"></i><p>No payments yet. Charges from completed appointments will appear here.</p></div><?php endif; ?>
  </div>
</div>

<?php dash_foot($page); ?>
