<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $success = "User deleted successfully.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if ($full_name && $email && $password && $role) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed, $role]);
            $success = "User account created successfully!";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Water Point System</title>
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
        .badge.admin { background: #e8f0fe; color: #1a73e8; }
        .badge.operator { background: #e8f5e9; color: #27ae60; }

        .delete-btn { color: #e74c3c; font-size: 13px; text-decoration: none; }
        .delete-btn:hover { text-decoration: underline; }

        .empty { text-align: center; color: #aaa; padding: 40px; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Water Point System</h2>
    <p>Wajir County</p>
    <a href="../dashboard.php">Dashboard</a>
    <a href="../waterpoints.php">Water Points</a>
    <a href="../maintenance.php">Maintenance</a>
    <a href="users.php" class="active">Manage Users</a>
    <div class="logout">
        <a href="../logout.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Manage Users</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <div class="form-section">
        <h3>Create New User Account</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="e.g. Ahmed Hassan" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="e.g. ahmed@wajirwater.go.ke" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" placeholder="Set a password for this user" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>
    </div>

    <div class="section">
        <h3>All Users</h3>
        <?php if (count($users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <a href="users.php?delete=<?= $u['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php else: ?>
                        <span style="color:#aaa; font-size:13px;">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No users found.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>