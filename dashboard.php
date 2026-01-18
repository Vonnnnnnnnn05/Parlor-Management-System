<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get today's income
$today = date('Y-m-d');
$manager_filter = $_SESSION['role'] === 'manager' ? " AND manager_id = {$_SESSION['user_id']}" : "";
$income_query = "SELECT COALESCE(SUM(total_amount), 0) as today_income 
                 FROM transactions 
                 WHERE DATE(transaction_date) = '$today' $manager_filter";
$income_result = mysqli_query($conn, $income_query);
$today_income = mysqli_fetch_assoc($income_result)['today_income'];

// Get today's quota
$quota_query = "SELECT quota_amount FROM daily_quota WHERE date = '$today'";
$quota_result = mysqli_query($conn, $quota_query);
$quota_row = mysqli_fetch_assoc($quota_result);
$quota_amount = $quota_row ? $quota_row['quota_amount'] : 0;

// Get system-wide income for quota calculation (always show total parlor income vs quota)
$system_income_query = "SELECT COALESCE(SUM(total_amount), 0) as system_income 
                        FROM transactions 
                        WHERE DATE(transaction_date) = '$today'";
$system_income_result = mysqli_query($conn, $system_income_query);
$system_income = mysqli_fetch_assoc($system_income_result)['system_income'];

// Calculate percentage based on system-wide income
$percentage = $quota_amount > 0 ? ($system_income / $quota_amount) * 100 : 0;

// Get total transactions today
$trans_count_query = "SELECT COUNT(*) as count FROM transactions WHERE DATE(transaction_date) = '$today' $manager_filter";
$trans_count_result = mysqli_query($conn, $trans_count_query);
$trans_count = mysqli_fetch_assoc($trans_count_result)['count'];

// Get active services count
$services_query = "SELECT COUNT(*) as count FROM services WHERE is_active = 1";
$services_result = mysqli_query($conn, $services_query);
$services_count = mysqli_fetch_assoc($services_result)['count'];

// Get total income (this month)
$first_day_month = date('Y-m-01');
$total_income_query = "SELECT COALESCE(SUM(total_amount), 0) as total_income 
                       FROM transactions 
                       WHERE DATE(transaction_date) >= '$first_day_month' $manager_filter";
$total_income_result = mysqli_query($conn, $total_income_query);
$total_income = mysqli_fetch_assoc($total_income_result)['total_income'];

// Get recent transactions
$recent_query = "SELECT t.*, u.name as manager_name, c.name as customer_name 
                 FROM transactions t
                 LEFT JOIN users u ON t.manager_id = u.id
                 LEFT JOIN customers c ON t.customer_id = c.id
                 WHERE 1=1 $manager_filter
                 ORDER BY t.transaction_date DESC
                 LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Parlor System</title>
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
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #6b7280;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-card.income .icon {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .stat-card.quota .icon {
            background-color: #fef3c7;
            color: #f59e0b;
        }

        .stat-card.total-income .icon {
            background-color: #d1fae5;
            color: #059669;
        }

        .stat-card.transactions .icon {
            background-color: #d1fae5;
            color: #10b981;
        }

        .stat-card.services .icon {
            background-color: #e0e7ff;
            color: #6366f1;
        }

        .stat-card h3 {
            color: #6b7280;
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .stat-card .progress-bar {
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
        }

        .stat-card .progress-fill {
            height: 100%;
            background-color: #10b981;
            transition: width 0.3s ease;
        }

        .stat-card .progress-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Recent Transactions */
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 25px;
        }

        .card h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
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

        .no-data {
            text-align: center;
            color: #9ca3af;
            padding: 40px;
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

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .value {
                font-size: 24px;
            }

            table {
                font-size: 13px;
            }

            table th,
            table td {
                padding: 10px 8px;
            }

            .card {
                padding: 20px;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 20px;
            }

            .stat-card .value {
                font-size: 20px;
            }

            table {
                font-size: 12px;
            }

            .badge {
                font-size: 10px;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card income">
                <div class="icon">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
                <h3><?php echo $_SESSION['role'] === 'admin' ? "Today's Income" : "My Income Today"; ?></h3>
                <div class="value">₱<?php echo number_format($today_income, 2); ?></div>
            </div>

            <div class="stat-card quota">
                <div class="icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3>Daily Quota</h3>
                <div class="value">₱<?php echo number_format($quota_amount, 2); ?></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                </div>
                <div class="progress-text">₱<?php echo number_format($system_income, 2); ?> earned • <?php echo number_format($percentage, 1); ?>% achieved</div>
            </div>

            <div class="stat-card total-income">
                <div class="icon">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <h3><?php echo $_SESSION['role'] === 'admin' ? "Total Income (This Month)" : "My Total Income (This Month)"; ?></h3>
                <div class="value">₱<?php echo number_format($total_income, 2); ?></div>
            </div>

            <div class="stat-card transactions">
                <div class="icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h3>Transactions Today</h3>
                <div class="value"><?php echo $trans_count; ?></div>
            </div>

            <div class="stat-card services">
                <div class="icon">
                    <i class="fa-solid fa-scissors"></i>
                </div>
                <h3>Active Services</h3>
                <div class="value"><?php echo $services_count; ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Recent Transactions</h2>
            <?php if (mysqli_num_rows($recent_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Manager</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo $row['customer_name'] ? htmlspecialchars($row['customer_name']) : 'Walk-in'; ?></td>
                            <td><?php echo htmlspecialchars($row['manager_name']); ?></td>
                            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><span class="badge <?php echo $row['payment_method']; ?>"><?php echo strtoupper($row['payment_method']); ?></span></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['transaction_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
                    <p>No transactions yet today</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Check for notifications
        const urlParams = new URLSearchParams(window.location.search);
        
        // Login success
        if (urlParams.get('login') === 'success') {
            Toastify({
                text: "✅ Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!",
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
        
        // Transaction success
        if (urlParams.get('success') === 'transaction') {
            Toastify({
                text: "💰 Transaction completed successfully!",
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
    </script>
</body>
</html>
