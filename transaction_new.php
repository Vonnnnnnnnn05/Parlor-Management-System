<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    $customer_name = !empty($_POST['customer_name']) ? mysqli_real_escape_string($conn, $_POST['customer_name']) : null;
    $contact_number = !empty($_POST['contact_number']) ? mysqli_real_escape_string($conn, $_POST['contact_number']) : null;
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $services = $_POST['services'] ?? [];
    $manager_id = $_SESSION['user_id'];

    if (!empty($services)) {
        // Insert customer if provided
        $customer_id = null;
        if ($customer_name) {
            $customer_query = "INSERT INTO customers (name, contact_number) VALUES ('$customer_name', '$contact_number')";
            mysqli_query($conn, $customer_query);
            $customer_id = mysqli_insert_id($conn);
        }

        // Calculate total
        $total = 0;
        foreach ($services as $service_id) {
            $price_query = "SELECT price FROM services WHERE id = $service_id";
            $price_result = mysqli_query($conn, $price_query);
            $price_row = mysqli_fetch_assoc($price_result);
            $total += $price_row['price'];
        }

        // Insert transaction
        $trans_query = "INSERT INTO transactions (customer_id, manager_id, total_amount, payment_method, notes) 
                        VALUES (" . ($customer_id ? $customer_id : "NULL") . ", $manager_id, $total, '$payment_method', '$notes')";
        mysqli_query($conn, $trans_query);
        $transaction_id = mysqli_insert_id($conn);

        // Insert transaction services
        foreach ($services as $service_id) {
            $price_query = "SELECT price FROM services WHERE id = $service_id";
            $price_result = mysqli_query($conn, $price_query);
            $price_row = mysqli_fetch_assoc($price_result);
            $price = $price_row['price'];

            $service_query = "INSERT INTO transaction_services (transaction_id, service_id, price) 
                             VALUES ($transaction_id, $service_id, $price)";
            mysqli_query($conn, $service_query);
        }

        header('Location: transaction_new.php?success=1');
        exit();
    }
}

// Get active services
$services_query = "SELECT * FROM services WHERE is_active = 1 ORDER BY service_name";
$services_result = mysqli_query($conn, $services_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Transaction - Parlor System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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

        .transaction-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
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

        .services-list {
            display: grid;
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
        }

        .service-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .service-item:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .service-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
        }

        .service-item.selected {
            border-color: #3b82f6;
            background-color: #dbeafe;
        }

        .service-info {
            flex: 1;
        }

        .service-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .service-duration {
            font-size: 13px;
            color: #6b7280;
        }

        .service-price {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
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

        .btn-primary:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        @media (max-width: 992px) {
            .transaction-grid {
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

            .card {
                padding: 20px;
            }

            .service-item {
                padding: 12px;
            }

            .service-name {
                font-size: 14px;
            }

            .service-price {
                font-size: 16px;
            }

            .btn {
                padding: 12px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .services-list {
                max-height: 300px;
            }

            .service-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .summary-total {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>New Transaction</h1>
            <p>Process customer payment with multiple services</p>
        </div>

        <form method="POST">
            <div class="transaction-grid">
                <div>
                    <div class="card" style="margin-bottom: 20px;">
                        <h2>Customer Information (Optional)</h2>
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" placeholder="Leave blank for walk-in">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" placeholder="Optional">
                        </div>
                    </div>

                    <div class="card">
                        <h2>Select Services</h2>
                        <div class="services-list">
                            <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                            <label class="service-item" onclick="toggleService(this)">
                                <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" 
                                       data-price="<?php echo $service['price']; ?>"
                                       data-name="<?php echo htmlspecialchars($service['service_name']); ?>"
                                       onchange="updateSummary()">
                                <div class="service-info">
                                    <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                    <div class="service-duration">
                                        <i class="fa-solid fa-clock"></i> <?php echo $service['duration_minutes']; ?> min
                                    </div>
                                </div>
                                <div class="service-price">₱<?php echo number_format($service['price'], 2); ?></div>
                            </label>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h2>Summary</h2>
                        <div id="selectedServices"></div>
                        <div class="summary-total">
                            <span>Total:</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>

                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="card">Card</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea name="notes" placeholder="Add any special notes"></textarea>
                        </div>

                        <button type="submit" name="submit_transaction" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="fa-solid fa-check"></i> Complete Transaction
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleService(label) {
            label.classList.toggle('selected');
        }

        function updateSummary() {
            const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
            const summaryDiv = document.getElementById('selectedServices');
            const totalDiv = document.getElementById('totalAmount');
            const submitBtn = document.getElementById('submitBtn');
            
            let total = 0;
            let html = '';

            checkboxes.forEach(cb => {
                const price = parseFloat(cb.dataset.price);
                const name = cb.dataset.name;
                total += price;

                html += `<div class="summary-item">
                    <span>${name}</span>
                    <span>₱${price.toFixed(2)}</span>
                </div>`;
            });

            summaryDiv.innerHTML = html || '<p style="color: #9ca3af; text-align: center; padding: 20px;">No services selected</p>';
            totalDiv.textContent = `₱${total.toFixed(2)}`;
            submitBtn.disabled = checkboxes.length === 0;
        }

        <?php if (isset($_GET['success'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            Toastify({
                text: "Transaction completed successfully!",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: "white",
                    color: "#065f46",
                    border: "2px solid #10b981",
                    borderRadius: "12px",
                    padding: "16px 24px",
                    boxShadow: "0 4px 6px rgba(0,0,0,0.1)"
                }
            }).showToast();
        });
        <?php endif; ?>
    </script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
