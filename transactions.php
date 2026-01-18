<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $id = intval($_POST['transaction_id']);

        // Check permission: admin can edit all, managers can only edit their own
        if ($_SESSION['role'] === 'manager') {
            $check_query = "SELECT manager_id FROM transactions WHERE id = $id";
            $check_result = mysqli_query($conn, $check_query);
            $check_row = mysqli_fetch_assoc($check_result);
            
            if (!$check_row || $check_row['manager_id'] != $_SESSION['user_id']) {
                header('Location: transactions.php?error=unauthorized');
                exit();
            }
        }

        if ($action === 'edit') {
            $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
            $notes = mysqli_real_escape_string($conn, $_POST['notes']);

            $query = "UPDATE transactions SET 
                      payment_method = '$payment_method',
                      notes = '$notes'
                      WHERE id = $id";
            mysqli_query($conn, $query);
            
            header('Location: transactions.php?success=updated');
            exit();
        } elseif ($action === 'delete') {
            // Only admin can delete
            if ($_SESSION['role'] !== 'admin') {
                header('Location: transactions.php?error=unauthorized');
                exit();
            }
            
            $query = "DELETE FROM transactions WHERE id = $id";
            mysqli_query($conn, $query);
            
            header('Location: transactions.php?success=deleted');
            exit();
        }
    }
}

// Get filter parameters
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';

// Build query
$manager_filter = $_SESSION['role'] === 'manager' ? " t.manager_id = {$_SESSION['user_id']}" : "1=1";
$query = "SELECT t.*, u.name as manager_name, c.name as customer_name 
          FROM transactions t
          LEFT JOIN users u ON t.manager_id = u.id
          LEFT JOIN customers c ON t.customer_id = c.id";

$where = [$manager_filter];
if ($date_filter) {
    $where[] = "DATE(t.transaction_date) = '$date_filter'";
}
if ($payment_filter !== 'all') {
    $where[] = "t.payment_method = '$payment_filter'";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY t.transaction_date DESC";
$result = mysqli_query($conn, $query);

// Get statistics for the filtered date
$stats_where = $_SESSION['role'] === 'manager' ? " AND manager_id = {$_SESSION['user_id']}" : "";
$stats_query = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(total_amount), 0) as total_amount
                FROM transactions 
                WHERE DATE(transaction_date) = '$date_filter' $stats_where";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Parlor System</title>
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

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }

        .stat-box.success {
            border-left-color: #10b981;
        }

        .stat-box.warning {
            border-left-color: #f59e0b;
        }

        .stat-box label {
            font-size: 13px;
            color: #6b7280;
            display: block;
            margin-bottom: 5px;
        }

        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
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

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-danger {
            background-color: #ef4444;
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

        .badge.cash {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge.gcash {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge.card {
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
            max-height: 90vh;
            overflow-y: auto;
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
        .form-group select,
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

        .service-list {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .service-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .filters {
                flex-direction: column;
                gap: 15px;
            }

            .filter-group {
                width: 100%;
            }

            .stats-row {
                flex-direction: column;
                gap: 15px;
            }

            .stat-box {
                width: 100%;
            }

            .card {
                padding: 15px;
                overflow-x: auto;
            }

            table {
                font-size: 12px;
            }

            table th,
            table td {
                padding: 8px 6px;
            }

            .action-buttons {
                flex-wrap: wrap;
                gap: 5px;
            }

            .btn-sm {
                padding: 6px 8px;
                font-size: 12px;
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
            <h1>Transaction History</h1>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
            </div>
            <div class="filter-group">
                <label>Payment Method</label>
                <select name="payment" onchange="this.form.submit()">
                    <option value="all" <?php echo $payment_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="cash" <?php echo $payment_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="gcash" <?php echo $payment_filter === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                    <option value="card" <?php echo $payment_filter === 'card' ? 'selected' : ''; ?>>Card</option>
                </select>
            </div>
        </form>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-box">
                <label>Total Transactions</label>
                <div class="value"><?php echo $stats['total_transactions']; ?></div>
            </div>
            <div class="stat-box success">
                <label>Total Revenue</label>
                <div class="value">₱<?php echo number_format($stats['total_amount'], 2); ?></div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Manager</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['transaction_date'])); ?></td>
                        <td><?php echo $row['customer_name'] ? htmlspecialchars($row['customer_name']) : 'Walk-in'; ?></td>
                        <td><?php echo htmlspecialchars($row['manager_name']); ?></td>
                        <td><strong>₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $row['payment_method']; ?>">
                                <?php echo strtoupper($row['payment_method']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="receipt.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" target="_blank" title="View Receipt">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                                <button class="btn btn-primary btn-sm" onclick='viewTransaction(<?php echo json_encode($row); ?>)' title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick='editTransaction(<?php echo json_encode($row); ?>)' title="Edit">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="transaction_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this transaction? This cannot be undone!')" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fa-solid fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
                <p>No transactions found for the selected filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <h2>Transaction Details</h2>
            <div id="transactionDetails"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Transaction</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="transaction_id" id="editTransactionId">

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="editPaymentMethod" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="card">Card</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="editNotes"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function viewTransaction(transaction) {
            // Get services for this transaction
            fetch(`get_transaction_services.php?id=${transaction.id}`)
                .then(response => response.json())
                .then(services => {
                    let servicesHtml = '<div class="service-list"><strong>Services:</strong>';
                    let total = 0;
                    services.forEach(service => {
                        servicesHtml += `<div class="service-item">
                            <span>${service.service_name}</span>
                            <span>₱${parseFloat(service.price).toFixed(2)}</span>
                        </div>`;
                        total += parseFloat(service.price);
                    });
                    servicesHtml += '</div>';

                    const html = `
                        <div class="form-group">
                            <label>Transaction ID</label>
                            <p>#${transaction.id}</p>
                        </div>
                        <div class="form-group">
                            <label>Date & Time</label>
                            <p>${new Date(transaction.transaction_date).toLocaleString()}</p>
                        </div>
                        <div class="form-group">
                            <label>Customer</label>
                            <p>${transaction.customer_name || 'Walk-in'}</p>
                        </div>
                        <div class="form-group">
                            <label>Manager</label>
                            <p>${transaction.manager_name}</p>
                        </div>
                        ${servicesHtml}
                        <div class="form-group">
                            <label>Total Amount</label>
                            <p><strong style="font-size: 20px; color: #10b981;">₱${total.toFixed(2)}</strong></p>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <p><span class="badge ${transaction.payment_method}">${transaction.payment_method.toUpperCase()}</span></p>
                        </div>
                        ${transaction.notes ? `<div class="form-group"><label>Notes</label><p>${transaction.notes}</p></div>` : ''}
                    `;
                    
                    document.getElementById('transactionDetails').innerHTML = html;
                    document.getElementById('viewModal').classList.add('active');
                });
        }

        function editTransaction(transaction) {
            document.getElementById('editTransactionId').value = transaction.id;
            document.getElementById('editPaymentMethod').value = transaction.payment_method;
            document.getElementById('editNotes').value = transaction.notes || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Success notifications
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        
        if (success) {
            let message = '';
            let emoji = '✅';
            
            switch(success) {
                case 'updated':
                    message = 'Transaction updated successfully!';
                    emoji = '🔄';
                    break;
                case 'deleted':
                    message = 'Transaction deleted successfully!';
                    emoji = '🗑️';
                    break;
                case 'unauthorized':
                    message = 'Unauthorized! You can only edit your own transactions.';
                    emoji = '🚫';
                    break;
            }
            
            if (message) {
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
                        border: "2px solid #10b981",
                        borderRadius: "12px",
                        boxShadow: "0 8px 24px rgba(16, 185, 129, 0.25)",
                        fontWeight: "600",
                        fontSize: "15px",
                        padding: "16px 24px"
                    }
                }).showToast();
            }
        }
    </script>
</body>
</html>
