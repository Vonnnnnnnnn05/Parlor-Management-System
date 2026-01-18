<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);

            // Check if username exists
            $check_query = "SELECT * FROM users WHERE username = '$username'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                header('Location: users.php?error=exists');
                exit();
            }

            $query = "INSERT INTO users (username, password, name, role) 
                      VALUES ('$username', '$password', '$name', '$role')";
            mysqli_query($conn, $query);
            $message = 'added';
        } elseif ($action === 'edit') {
            $id = intval($_POST['user_id']);
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);

            $query = "UPDATE users SET 
                      username = '$username',
                      name = '$name',
                      role = '$role'";
            
            // Only update password if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query .= ", password = '$password'";
            }
            
            $query .= " WHERE id = $id";
            mysqli_query($conn, $query);
            $message = 'updated';
        } elseif ($action === 'deactivate') {
            $id = intval($_POST['user_id']);
            // Prevent deactivating yourself
            if ($id == $_SESSION['user_id']) {
                header('Location: users.php?error=self');
                exit();
            }
            $query = "UPDATE users SET is_active = 0 WHERE id = $id";
            mysqli_query($conn, $query);
            $message = 'deactivated';
        } elseif ($action === 'activate') {
            $id = intval($_POST['user_id']);
            $query = "UPDATE users SET is_active = 1 WHERE id = $id";
            mysqli_query($conn, $query);
            $message = 'activated';
        }
        
        header('Location: users.php?success=' . $message);
        exit();
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY role ASC, name ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Parlor System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f3f4f6;
        }

        .main-content {
            margin-left: 220px;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background-color: #f9fafb;
        }

        table th {
            text-align: left;
            padding: 12px;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        table td {
            padding: 12px;
            color: #1f2937;
            border-bottom: 1px solid #f3f4f6;
        }

        table tr:hover {
            background-color: #f9fafb;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge.inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge.admin {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge.manager {
            background-color: #e0e7ff;
            color: #4338ca;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #374151;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .btn {
                width: 100%;
            }

            .card {
                padding: 15px;
                overflow-x: auto;
            }

            table {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Manage Users</h1>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fa-solid fa-user-plus"></i> Add User
            </button>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <span class="badge <?php echo $row['role']; ?>">
                                <?php echo strtoupper($row['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick='editUser(<?php echo json_encode($row); ?>)'>
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($row['is_active']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deactivate this user?')">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add User</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="name" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" required>
                    <div class="password-hint" id="passwordHint">Minimum 6 characters recommended</div>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="role" required>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('username').value = '';
            document.getElementById('name').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').textContent = 'Minimum 6 characters recommended';
            document.getElementById('role').value = 'manager';
            document.getElementById('userModal').classList.add('active');
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('name').value = user.name;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordHint').textContent = 'Leave blank to keep current password';
            document.getElementById('role').value = user.role;
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Success notifications
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        const error = urlParams.get('error');
        
        if (success) {
            let message = '';
            let emoji = '✅';
            
            switch(success) {
                case 'added':
                    message = 'User added successfully!';
                    break;
                case 'updated':
                    message = 'User updated successfully!';
                    emoji = '🔄';
                    break;
                case 'deactivated':
                    message = 'User deactivated!';
                    emoji = '🔴';
                    break;
                case 'activated':
                    message = 'User activated!';
                    emoji = '🟢';
                    break;
            }
            
            if (message) {
                let borderColor = '#10b981';
                let shadowColor = 'rgba(16, 185, 129, 0.25)';
                
                if (success === 'deactivated') {
                    borderColor = '#ef4444';
                    shadowColor = 'rgba(239, 68, 68, 0.25)';
                }
                
                Toastify({
                    text: emoji + ' ' + message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#ffffff",
                    stopOnFocus: true,
                    style: {
                        background: "#ffffff",
                        color: "#1f2937",
                        border: "2px solid " + borderColor,
                        borderRadius: "12px",
                        boxShadow: "0 8px 24px " + shadowColor,
                        fontWeight: "600",
                        fontSize: "15px",
                        padding: "16px 24px"
                    }
                }).showToast();
            }
        }
        
        if (error) {
            let message = '';
            
            switch(error) {
                case 'exists':
                    message = '❌ Username already exists!';
                    break;
                case 'self':
                    message = '❌ You cannot deactivate yourself!';
                    break;
                case 'unauthorized':
                    message = '❌ Only admins can manage users!';
                    break;
            }
            
            if (message) {
                Toastify({
                    text: message,
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#ffffff",
                    stopOnFocus: true,
                    style: {
                        background: "#ffffff",
                        color: "#dc2626",
                        border: "2px solid #dc2626",
                        borderRadius: "12px",
                        boxShadow: "0 8px 24px rgba(220, 38, 38, 0.25)",
                        fontWeight: "600",
                        fontSize: "15px",
                        padding: "16px 24px"
                    }
                }).showToast();
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
