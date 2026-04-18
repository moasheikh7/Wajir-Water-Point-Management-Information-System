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
$waterPoints = $pdo->query("SELECT * FROM water_points ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Water Point System</title>
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

        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .card .label { font-size: 12px; color: #888; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .number { font-size: 32px; font-weight: bold; color: #333; }
        .card.blue .number { color: #1a73e8; }
        .card.green .number { color: #27ae60; }
        .card.red .number { color: #e74c3c; }
        .card.orange .number { color: #f39c12; }

        .section { background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .section h3 { font-size: 16px; color: #333; margin-bottom: 16px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #888; text-transform: uppercase; padding: 10px 12px; border-bottom: 1px solid #eee; }
        td { padding: 12px; font-size: 14px; color: #444; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.operational { background: #e8f5e9; color: #27ae60; }
        .badge.non_operational { background: #fdecea; color: #e74c3c; }
        .badge.under_maintenance { background: #fff8e1; color: #f39c12; }

        .empty { text-align: center; color: #aaa; padding: 40px; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Water Point System</h2>
    <p>Wajir County</p>
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="waterpoints.php">Water Points</a>
    <a href="maintenance.php">Maintenance</a>
    <a href="reports.php">Reports</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin/users.php">Manage Users</a>
    <?php endif; ?>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Dashboard</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <div class="cards">
        <div class="card blue">
            <div class="label">Total Water Points</div>
            <div class="number"><?= $totalWaterPoints ?></div>
        </div>
        <div class="card green">
            <div class="label">Operational</div>
            <div class="number"><?= $operational ?></div>
        </div>
        <div class="card red">
            <div class="label">Non Operational</div>
            <div class="number"><?= $nonOperational ?></div>
        </div>
        <div class="card orange">
            <div class="label">Under Maintenance</div>
            <div class="number"><?= $underMaintenance ?></div>
        </div>
    </div>

    <div class="section">
        <h3>Recent Water Points</h3>
        <?php if (count($waterPoints) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($waterPoints as $wp): ?>
                <tr>
                    <td><?= htmlspecialchars($wp['name']) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $wp['type'])) ?></td>
                    <td><?= htmlspecialchars($wp['location_desc']) ?></td>
                    <td><span class="badge <?= $wp['status'] ?>"><?= ucfirst(str_replace('_', ' ', $wp['status'])) ?></span></td>
                    <td><?= date('d M Y', strtotime($wp['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No water points registered yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>