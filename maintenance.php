<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $water_point_id = $_POST['water_point_id'];
    $breakdown_date = $_POST['breakdown_date'];
    $description = trim($_POST['description']);
    $action_taken = trim($_POST['action_taken']);
    $technician_name = trim($_POST['technician_name']);
    $repair_date = $_POST['repair_date'] ?: null;
    $status = $_POST['status'];

    if ($water_point_id && $breakdown_date && $description) {
        $stmt = $pdo->prepare("INSERT INTO maintenance_records (water_point_id, reported_by, breakdown_date, description, action_taken, technician_name, repair_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$water_point_id, $_SESSION['user_id'], $breakdown_date, $description, $action_taken ?: null, $technician_name ?: null, $repair_date, $status]);

        if ($status === 'in_progress' || $status === 'pending') {
            $pdo->prepare("UPDATE water_points SET status = 'under_maintenance' WHERE id = ?")->execute([$water_point_id]);
        } elseif ($status === 'resolved') {
            $pdo->prepare("UPDATE water_points SET status = 'operational' WHERE id = ?")->execute([$water_point_id]);
        }

        $success = "Maintenance record logged successfully!";
    } else {
        $error = "Please fill in all required fields.";
    }
}

$waterPoints = $pdo->query("SELECT id, name FROM water_points ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$records = $pdo->query("SELECT m.*, w.name AS wp_name, u.full_name FROM maintenance_records m JOIN water_points w ON m.water_point_id = w.id JOIN users u ON m.reported_by = u.id ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance | Water Point System</title>
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

        .success { background: #e8f5e9; color: #27ae60; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .error { background: #fdecea; color: #e74c3c; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }

        .form-section {
            background: white;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 30px;
        }
        .form-section h3 { font-size: 16px; color: #333; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 13px; color: #555; margin-bottom: 5px; }
        input, select, textarea {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            font-family: Arial, sans-serif;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1a73e8; }
        textarea { resize: vertical; min-height: 80px; }

        .required { color: #e74c3c; }

        .btn {
            margin-top: 20px;
            padding: 11px 28px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn:hover { background: #1558b0; }

        .section {
            background: white;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .section h3 { font-size: 16px; color: #333; margin-bottom: 16px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #888; text-transform: uppercase; padding: 10px 12px; border-bottom: 1px solid #eee; }
        td { padding: 12px; font-size: 14px; color: #444; border-bottom: 1px solid #f5f5f5; vertical-align: top; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.pending { background: #fdecea; color: #e74c3c; }
        .badge.in_progress { background: #fff8e1; color: #f39c12; }
        .badge.resolved { background: #e8f5e9; color: #27ae60; }

        .empty { text-align: center; color: #aaa; padding: 40px; font-size: 14px; }
        .hint { font-size: 12px; color: #aaa; margin-top: 4px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Water Point System</h2>
    <p>Wajir County</p>
    <a href="dashboard.php">Dashboard</a>
    <a href="waterpoints.php">Water Points</a>
    <a href="maintenance.php" class="active">Maintenance</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin/users.php">Manage Users</a>
    <?php endif; ?>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Maintenance</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <div class="form-section">
        <h3>Log Maintenance / Breakdown</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Water Point <span class="required">*</span></label>
                    <select name="water_point_id" required>
                        <option value="">Select water point</option>
                        <?php foreach ($waterPoints as $wp): ?>
                        <option value="<?= $wp['id'] ?>"><?= htmlspecialchars($wp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Breakdown Date <span class="required">*</span></label>
                    <input type="date" name="breakdown_date" required>
                </div>
                <div class="form-group">
                    <label>Maintenance Status <span class="required">*</span></label>
                    <select name="status" required>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Technician Name</label>
                    <input type="text" name="technician_name" placeholder="Name of technician assigned">
                </div>
                <div class="form-group full">
                    <label>Description of Problem <span class="required">*</span></label>
                    <textarea name="description" placeholder="Describe what went wrong..."></textarea>
                </div>
                <div class="form-group full">
                    <label>Action Taken</label>
                    <textarea name="action_taken" placeholder="Describe what was done to fix it (leave blank if not yet resolved)..."></textarea>
                </div>
                <div class="form-group">
                    <label>Repair Date</label>
                    <input type="date" name="repair_date">
                    <span class="hint">Leave blank if not yet repaired</span>
                </div>
            </div>
            <button type="submit" class="btn">Log Maintenance Record</button>
        </form>
    </div>

    <div class="section">
        <h3>All Maintenance Records</h3>
        <?php if (count($records) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Water Point</th>
                    <th>Breakdown Date</th>
                    <th>Problem</th>
                    <th>Action Taken</th>
                    <th>Technician</th>
                    <th>Repair Date</th>
                    <th>Status</th>
                    <th>Reported By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['wp_name']) ?></td>
                    <td><?= date('d M Y', strtotime($r['breakdown_date'])) ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= $r['action_taken'] ? htmlspecialchars($r['action_taken']) : '—' ?></td>
                    <td><?= $r['technician_name'] ? htmlspecialchars($r['technician_name']) : '—' ?></td>
                    <td><?= $r['repair_date'] ? date('d M Y', strtotime($r['repair_date'])) : '—' ?></td>
                    <td><span class="badge <?= $r['status'] ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No maintenance records logged yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
