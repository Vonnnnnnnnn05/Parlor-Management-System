<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle Add/Edit/Delete (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if ($_SESSION['role'] !== 'admin') {
        header('Location: services.php?error=unauthorized');
        exit();
    }
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $name = mysqli_real_escape_string($conn, $_POST['service_name']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration_minutes']);

            $query = "INSERT INTO services (service_name, description, price, duration_minutes) 
                      VALUES ('$name', '$description', $price, $duration)";
            mysqli_query($conn, $query);
        } elseif ($action === 'edit') {
            $id = intval($_POST['service_id']);
            $name = mysqli_real_escape_string($conn, $_POST['service_name']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration_minutes']);

            $query = "UPDATE services SET 
                      service_name = '$name',
                      description = '$description',
                      price = $price,
                      duration_minutes = $duration
                      WHERE id = $id";
            mysqli_query($conn, $query);
        } elseif ($action === 'delete') {
            $id = intval($_POST['service_id']);
            $query = "UPDATE services SET is_active = 0 WHERE id = $id";
            mysqli_query($conn, $query);
        } elseif ($action === 'activate') {
            $id = intval($_POST['service_id']);
            $query = "UPDATE services SET is_active = 1 WHERE id = $id";
            mysqli_query($conn, $query);
        }
        
        $message = '';
        if ($action === 'add') $message = 'added';
        elseif ($action === 'edit') $message = 'updated';
        elseif ($action === 'delete') $message = 'deactivated';
        elseif ($action === 'activate') $message = 'activated';
        
        header('Location: services.php?success=' . $message);
        exit();
    }
}

// Get all services
$query = "SELECT * FROM services ORDER BY is_active DESC, service_name ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Parlor System</title>
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
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

            .action-buttons button,
            .action-buttons form {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><?php echo $_SESSION['role'] === 'admin' ? 'Manage Services' : 'View Services'; ?></h1>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i> Add Service
            </button>
            <?php endif; ?>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['service_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['duration_minutes']; ?> min</td>
                        <td>
                            <span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick='editService(<?php echo json_encode($row); ?>)'>
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <?php if ($row['is_active']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deactivate this service?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="service_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="serviceModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Service</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="service_id" id="serviceId">

                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" name="service_name" id="serviceName" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description"></textarea>
                </div>

                <div class="form-group">
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" name="price" id="price" required>
                </div>

                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="duration" required>
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
            document.getElementById('modalTitle').textContent = 'Add Service';
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceName').value = '';
            document.getElementById('description').value = '';
            document.getElementById('price').value = '';
            document.getElementById('duration').value = '30';
            document.getElementById('serviceModal').classList.add('active');
        }

        function editService(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.service_name;
            document.getElementById('description').value = service.description;
            document.getElementById('price').value = service.price;
            document.getElementById('duration').value = service.duration_minutes;
            document.getElementById('serviceModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('serviceModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Success notifications
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        
        if (success) {
            let message = '';
            let emoji = '✅';
            
            switch(success) {
                case 'added':
                    message = 'Service added successfully!';
                    break;
                case 'updated':
                    message = 'Service updated successfully!';
                    emoji = '🔄';
                    break;
                case 'deactivated':
                    message = 'Service deactivated!';
                    emoji = '🔴';
                    break;
                case 'activated':
                    message = 'Service activated!';
                    emoji = '🟢';
                    break;
                case 'unauthorized':
                    message = 'Unauthorized! Only admins can modify services.';
                    emoji = '🚫';
                    break;
            }
            
            if (message) {
                let borderColor = '#10b981';
                let shadowColor = 'rgba(16, 185, 129, 0.25)';
                
                if (success === 'deactivated' || success === 'unauthorized') {
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
