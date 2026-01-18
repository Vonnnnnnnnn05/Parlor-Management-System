<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle quota update (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_quota'])) {
    // Check if user is admin
    if ($_SESSION['role'] !== 'admin') {
        header('Location: quota_set.php?error=unauthorized');
        exit();
    }
    
    $date = mysqli_real_escape_string($conn, $_POST['quota_date']);
    $amount = floatval($_POST['quota_amount']);

    $check_query = "SELECT * FROM daily_quota WHERE date = '$date'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Update existing
        $query = "UPDATE daily_quota SET quota_amount = $amount WHERE date = '$date'";
    } else {
        // Insert new
        $query = "INSERT INTO daily_quota (quota_amount, date) VALUES ($amount, '$date')";
    }
    
    mysqli_query($conn, $query);
    header('Location: quota_set.php?success=1');
    exit();
}

// Get quotas for next 30 days
$query = "SELECT * FROM daily_quota WHERE date >= CURDATE() ORDER BY date ASC LIMIT 30";
$result = mysqli_query($conn, $query);

// Get today's quota and income
$today = date('Y-m-d');
$today_query = "SELECT 
    (SELECT quota_amount FROM daily_quota WHERE date = '$today') as quota,
    (SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE DATE(transaction_date) = '$today') as income";
$today_result = mysqli_query($conn, $today_query);
$today_data = mysqli_fetch_assoc($today_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Quota - Parlor System</title>
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

        .quota-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }

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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .today-status {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .today-status h3 {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .today-status .amount {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 10px;
            background-color: rgba(255,255,255,0.3);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            background-color: white;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 14px;
            opacity: 0.9;
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

        .badge.today {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            width: auto;
        }

        @media (max-width: 992px) {
            .quota-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .today-status {
                padding: 20px;
            }

            .today-status h3 {
                font-size: 16px;
            }

            .today-status .amount {
                font-size: 20px;
            }

            .card {
                padding: 20px;
            }

            table {
                font-size: 13px;
                overflow-x: auto;
                display: block;
            }

            table th,
            table td {
                padding: 10px 8px;
            }

            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><?php echo $_SESSION['role'] === 'admin' ? 'Daily Quota Management' : 'Daily Quota Status'; ?></h1>
            <p><?php echo $_SESSION['role'] === 'admin' ? 'Set and track daily income targets' : 'Track parlor daily income targets'; ?></p>
        </div>

        <?php if ($today_data['quota']): ?>
        <div class="today-status">
            <h3>Today's Progress</h3>
            <div class="amount">₱<?php echo number_format($today_data['income'], 2); ?> / ₱<?php echo number_format($today_data['quota'], 2); ?></div>
            <?php 
            $percentage = ($today_data['income'] / $today_data['quota']) * 100;
            ?>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
            </div>
            <div class="progress-text"><?php echo number_format($percentage, 1); ?>% of daily quota achieved</div>
        </div>
        <?php endif; ?>

        <div class="quota-grid">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="card">
                <h2>Set Quota</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="quota_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Quota Amount (₱)</label>
                        <input type="number" step="0.01" name="quota_amount" placeholder="5000.00" required>
                    </div>

                    <button type="submit" name="set_quota" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Set Quota
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="card" style="<?php echo $_SESSION['role'] === 'manager' ? 'grid-column: 1 / -1;' : ''; ?>">
                <h2>Upcoming Quotas</h2>
                <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Quota Amount</th>
                            <th>Status</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                <?php if ($row['date'] == $today): ?>
                                <span class="badge today">TODAY</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>₱<?php echo number_format($row['quota_amount'], 2); ?></strong></td>
                            <td>
                                <?php
                                $date_income_query = "SELECT COALESCE(SUM(total_amount), 0) as income 
                                                     FROM transactions 
                                                     WHERE DATE(transaction_date) = '{$row['date']}'";
                                $date_income_result = mysqli_query($conn, $date_income_query);
                                $date_income = mysqli_fetch_assoc($date_income_result)['income'];
                                $date_percentage = ($date_income / $row['quota_amount']) * 100;
                                
                                if ($row['date'] < $today) {
                                    echo number_format($date_percentage, 0) . '%';
                                } else {
                                    echo '---';
                                }
                                ?>
                            </td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="editQuota('<?php echo $row['date']; ?>', <?php echo $row['quota_amount']; ?>)">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #9ca3af; padding: 40px;">No quotas set for upcoming days</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function editQuota(date, amount) {
            document.querySelector('input[name="quota_date"]').value = date;
            document.querySelector('input[name="quota_amount"]').value = amount;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Check for quota success
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            Toastify({
                text: "🎯 Quota set successfully!",
                duration: 3000,
                gravity: "top",
                position: "right",
                    backgroundColor: "#ffffff",
                stopOnFocus: true,
                style: {
                    background: "#ffffff",
                    color: "#1f2937",
                    border: "2px solid #f59e0b",
                    borderRadius: "12px",
                    boxShadow: "0 8px 24px rgba(245, 158, 11, 0.25)",
                    fontWeight: "600",
                    fontSize: "15px",
                    padding: "16px 24px"
                }
            }).showToast();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
