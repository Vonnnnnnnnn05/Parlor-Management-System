<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$transaction_id = intval($_GET['id']);

$query = "SELECT ts.*, s.service_name 
          FROM transaction_services ts
          INNER JOIN services s ON ts.service_id = s.id
          WHERE ts.transaction_id = $transaction_id";

$result = mysqli_query($conn, $query);
$services = [];

while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

header('Content-Type: application/json');
echo json_encode($services);
?>
