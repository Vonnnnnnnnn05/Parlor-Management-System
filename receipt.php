<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$transaction_id = intval($_GET['id']);

// Get transaction details
$query = "SELECT t.*, u.name as manager_name, c.name as customer_name, c.contact_number 
          FROM transactions t
          LEFT JOIN users u ON t.manager_id = u.id
          LEFT JOIN customers c ON t.customer_id = c.id
          WHERE t.id = $transaction_id";
$result = mysqli_query($conn, $query);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    header('Location: transactions.php');
    exit();
}

// Get services
$services_query = "SELECT ts.*, s.service_name, s.duration_minutes 
                   FROM transaction_services ts
                   INNER JOIN services s ON ts.service_id = s.id
                   WHERE ts.transaction_id = $transaction_id";
$services_result = mysqli_query($conn, $services_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $transaction_id; ?> - Parlor System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
        }

        body {
            background-color: #f3f4f6;
            padding: 20px;
        }

        .actions {
            max-width: 400px;
            margin: 0 auto 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s;
            font-family: Arial, sans-serif;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .receipt-header p {
            font-size: 12px;
            margin: 2px 0;
        }

        .receipt-info {
            margin-bottom: 20px;
            font-size: 13px;
        }

        .receipt-info .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .receipt-info .label {
            font-weight: bold;
        }

        .services-table {
            width: 100%;
            margin: 20px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 15px 0;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
        }

        .service-item .name {
            flex: 1;
        }

        .service-item .price {
            text-align: right;
            font-weight: bold;
        }

        .totals {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .total-row.grand {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #000;
            font-size: 12px;
        }

        .receipt-footer p {
            margin: 5px 0;
        }

        .thank-you {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                width: 80mm;
                display: flex;
                justify-content: center;
            }

            .actions {
                display: none;
            }

            .receipt-container {
                box-shadow: none;
                max-width: 80mm;
                width: 80mm;
                padding: 10mm;
                margin: 0 auto;
            }

            .receipt-header h1 {
                font-size: 18px;
            }

            .receipt-header h2 {
                font-size: 14px;
            }

            .receipt-header p,
            .receipt-info,
            .service-item,
            .total-row,
            .receipt-footer {
                font-size: 11px;
            }

            .total-row.grand {
                font-size: 14px;
            }

            .thank-you {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <button onclick="downloadReceipt()" class="btn btn-success">
            <i class="fa-solid fa-download"></i> Download
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-home"></i> Dashboard
        </a>
    </div>

    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <h1>✂️ SHIENA BELMES</h1>
            <h2>BEAUTY PARLOR</h2>
            <p>Professional Parlor Services</p>
            <p>Phone: (123) 456-7890</p>
            <p>Email: info@shienabeauty.com</p>
        </div>

        <div class="receipt-info">
            <div class="row">
                <span class="label">RECEIPT NO:</span>
                <span>#<?php echo str_pad($transaction_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="row">
                <span class="label">DATE:</span>
                <span><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></span>
            </div>
            <div class="row">
                <span class="label">CUSTOMER:</span>
                <span><?php echo $transaction['customer_name'] ? htmlspecialchars($transaction['customer_name']) : 'Walk-in Customer'; ?></span>
            </div>
            <?php if ($transaction['contact_number']): ?>
            <div class="row">
                <span class="label">CONTACT:</span>
                <span><?php echo htmlspecialchars($transaction['contact_number']); ?></span>
            </div>
            <?php endif; ?>
            <div class="row">
                <span class="label">SERVED BY:</span>
                <span><?php echo htmlspecialchars($transaction['manager_name']); ?></span>
            </div>
            <div class="row">
                <span class="label">PAYMENT:</span>
                <span><?php echo strtoupper($transaction['payment_method']); ?></span>
            </div>
        </div>

        <div class="services-table">
            <div class="service-item" style="font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 8px; margin-bottom: 12px;">
                <span class="name">SERVICE</span>
                <span class="price">PRICE</span>
            </div>
            
            <?php 
            $subtotal = 0;
            while ($service = mysqli_fetch_assoc($services_result)): 
                $subtotal += $service['price'];
            ?>
            <div class="service-item">
                <span class="name"><?php echo htmlspecialchars($service['service_name']); ?></span>
                <span class="price">₱<?php echo number_format($service['price'], 2); ?></span>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="totals">
            <div class="total-row grand">
                <span>TOTAL:</span>
                <span>₱<?php echo number_format($transaction['total_amount'], 2); ?></span>
            </div>
        </div>

        <?php if ($transaction['notes']): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f3f4f6; border-radius: 4px; font-size: 12px;">
            <strong>Notes:</strong> <?php echo htmlspecialchars($transaction['notes']); ?>
        </div>
        <?php endif; ?>

        <div class="receipt-footer">
            <p class="thank-you">THANK YOU FOR YOUR VISIT!</p>
            <p>We appreciate your business</p>
            <p>Please come again soon!</p>
            <p style="margin-top: 15px;">================================</p>
            <p>This serves as your official receipt</p>
            <p>Printed: <?php echo date('M d, Y h:i A'); ?></p>
        </div>
    </div>

    <script>
        function downloadReceipt() {
            window.print();
        }

        // Auto-focus print button
        document.addEventListener('DOMContentLoaded', function() {
            // Optional: auto-print on load (uncomment if desired)
            // setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>
