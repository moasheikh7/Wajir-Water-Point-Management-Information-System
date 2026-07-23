<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    $id = $_GET['delete'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM maintenance_records WHERE water_point_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $error = "Cannot delete this water point — it has maintenance records linked to it.";
    } else {
        $pdo->prepare("DELETE FROM water_points WHERE id = ?")->execute([$id]);
        $success = "Water point deleted successfully.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $location_desc = trim($_POST['location_desc']);
    $status = $_POST['status'];

    if ($name && $type && $latitude && $longitude && $location_desc && $status) {
        $stmt = $pdo->prepare("INSERT INTO water_points (name, type, latitude, longitude, location_desc, status, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $latitude, $longitude, $location_desc, $status, $_SESSION['user_id']]);
        $success = "Water point registered successfully!";
    } else {
        $error = "Please fill in all fields.";
    }
}

$waterPoints = $pdo->query("SELECT w.*, u.full_name FROM water_points w JOIN users u ON w.registered_by = u.id ORDER BY w.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Water Points | Water Point System</title>
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
        input, select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
        }
        input:focus, select:focus { outline: none; border-color: #1a73e8; }

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
        td { padding: 12px; font-size: 14px; color: #444; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.operational { background: #e8f5e9; color: #27ae60; }
        .badge.non_operational { background: #fdecea; color: #e74c3c; }
        .badge.under_maintenance { background: #fff8e1; color: #f39c12; }

        .delete-btn { color: #e74c3c; font-size: 13px; text-decoration: none; }
        .delete-btn:hover { text-decoration: underline; }

        .empty { text-align: center; color: #aaa; padding: 40px; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Water Point System</h2>
    <p>Wajir County</p>
    <a href="dashboard.php">Dashboard</a>
    <a href="waterpoints.php" class="active">Water Points</a>
    <a href="maintenance.php">Maintenance</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin/users.php">Manage Users</a>
    <?php endif; ?>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Water Points</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <div class="form-section">
        <h3>Register New Water Point</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Water Point Name</label>
                    <input type="text" name="name" placeholder="e.g. Wajir Town Borehole 1" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="">Select type</option>
                        <option value="borehole">Borehole</option>
                        <option value="well">Well</option>
                        <option value="water_pan">Water Pan</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Latitude (GPS)</label>
                    <input type="text" name="latitude" placeholder="e.g. 1.7471">
                </div>
                <div class="form-group">
                    <label>Longitude (GPS)</label>
                    <input type="text" name="longitude" placeholder="e.g. 40.0573">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="operational">Operational</option>
                        <option value="non_operational">Non Operational</option>
                        <option value="under_maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Location Description</label>
                    <input type="text" name="location_desc" placeholder="e.g. Near Wajir town market, central district" required>
                </div>
            </div>
            <button type="submit" class="btn">Register Water Point</button>
        </form>
    </div>

    <div class="section">
        <h3>All Water Points</h3>
        <?php if (count($waterPoints) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>GPS</th>
                    <th>Status</th>
                    <th>Registered By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($waterPoints as $wp): ?>
                <tr>
                    <td><?= htmlspecialchars($wp['name']) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $wp['type'])) ?></td>
                    <td><?= htmlspecialchars($wp['location_desc']) ?></td>
                    <td><?= $wp['latitude'] ?>, <?= $wp['longitude'] ?></td>
                    <td><span class="badge <?= $wp['status'] ?>"><?= ucfirst(str_replace('_', ' ', $wp['status'])) ?></span></td>
                    <td><?= htmlspecialchars($wp['full_name']) ?></td>
                    <td><?= date('d M Y', strtotime($wp['created_at'])) ?></td>
                    <td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="waterpoints.php?delete=<?= $wp['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this water point?')">Delete</a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
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
