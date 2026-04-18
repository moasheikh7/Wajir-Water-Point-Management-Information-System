<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$totalWaterPoints = $pdo->query("SELECT COUNT(*) FROM water_points")->fetchColumn();
$operational = $pdo->query("SELECT COUNT(*) FROM water_points WHERE status = 'operational'")->fetchColumn();
$nonOperational = $pdo->query("SELECT COUNT(*) FROM water_points WHERE status = 'non_operational'")->fetchColumn();
$underMaintenance = $pdo->query("SELECT COUNT(*) FROM water_points WHERE status = 'under_maintenance'")->fetchColumn();
$totalMaintenance = $pdo->query("SELECT COUNT(*) FROM maintenance_records")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'pending'")->fetchColumn();
$inProgress = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'in_progress'")->fetchColumn();
$resolved = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'resolved'")->fetchColumn();
$byType = $pdo->query("SELECT type, COUNT(*) as total FROM water_points GROUP BY type")->fetchAll(PDO::FETCH_ASSOC);
$recentMaintenance = $pdo->query("SELECT m.*, w.name AS wp_name, u.full_name FROM maintenance_records m JOIN water_points w ON m.water_point_id = w.id JOIN users u ON m.reported_by = u.id ORDER BY m.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Water Point System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; display: flex; }

        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #1a73e8;
            color: white;
            padding: 30px 20px;
            position: fixed;
        }
        .sidebar h2 { font-size: 16px; margin-bottom: 6px; }
        .sidebar p { font-size: 12px; opacity: 0.8; margin-bottom: 30px; }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .sidebar .logout { margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; }

        .main { margin-left: 240px; padding: 30px; width: 100%; }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar h1 { font-size: 22px; color: #333; }
        .topbar span { font-size: 13px; color: #666; }

        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }

        .section {
            background: white;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .section h3 { font-size: 16px; color: #333; margin-bottom: 20px; }

        .stat-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f5f5f5; }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { font-size: 14px; color: #555; }
        .stat-value { font-size: 18px; font-weight: bold; color: #333; }
        .stat-value.blue { color: #1a73e8; }
        .stat-value.green { color: #27ae60; }
        .stat-value.red { color: #e74c3c; }
        .stat-value.orange { color: #f39c12; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #888; text-transform: uppercase; padding: 10px 12px; border-bottom: 1px solid #eee; }
        td { padding: 12px; font-size: 14px; color: #444; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.pending { background: #fdecea; color: #e74c3c; }
        .badge.in_progress { background: #fff8e1; color: #f39c12; }
        .badge.resolved { background: #e8f5e9; color: #27ae60; }

        .empty { text-align: center; color: #aaa; padding: 40px; font-size: 14px; }

        .full { grid-column: span 2; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Water Point System</h2>
    <p>Wajir County</p>
    <a href="dashboard.php">Dashboard</a>
    <a href="waterpoints.php">Water Points</a>
    <a href="maintenance.php">Maintenance</a>
    <a href="reports.php" class="active">Reports</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin/users.php">Manage Users</a>
    <?php endif; ?>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Reports</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <div class="grid2">
        <div class="section">
            <h3>Water Point Summary</h3>
            <div class="stat-row">
                <span class="stat-label">Total Water Points</span>
                <span class="stat-value blue"><?= $totalWaterPoints ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Operational</span>
                <span class="stat-value green"><?= $operational ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Non Operational</span>
                <span class="stat-value red"><?= $nonOperational ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Under Maintenance</span>
                <span class="stat-value orange"><?= $underMaintenance ?></span>
            </div>
        </div>

        <div class="section">
            <h3>Maintenance Summary</h3>
            <div class="stat-row">
                <span class="stat-label">Total Records</span>
                <span class="stat-value blue"><?= $totalMaintenance ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Pending</span>
                <span class="stat-value red"><?= $pending ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">In Progress</span>
                <span class="stat-value orange"><?= $inProgress ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Resolved</span>
                <span class="stat-value green"><?= $resolved ?></span>
            </div>
        </div>

        <div class="section">
            <h3>Water Points by Type</h3>
            <?php if (count($byType) > 0): ?>
                <?php foreach ($byType as $t): ?>
                <div class="stat-row">
                    <span class="stat-label"><?= ucfirst(str_replace('_', ' ', $t['type'])) ?></span>
                    <span class="stat-value blue"><?= $t['total'] ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No data yet.</div>
            <?php endif; ?>
        </div>

        <div class="section full">
            <h3>Recent Maintenance Activity</h3>
            <?php if (count($recentMaintenance) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Water Point</th>
                        <th>Breakdown Date</th>
                        <th>Problem</th>
                        <th>Status</th>
                        <th>Reported By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMaintenance as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['wp_name']) ?></td>
                        <td><?= date('d M Y', strtotime($r['breakdown_date'])) ?></td>
                        <td><?= htmlspecialchars($r['description']) ?></td>
                        <td><span class="badge <?= $r['status'] ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">No maintenance records yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>