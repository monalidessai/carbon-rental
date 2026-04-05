<?php
include('includes/db_connect.php');

$customer_id = $_POST['customer_id'];
$message = $_POST['message'];

$sql = "INSERT INTO feedback (customer_id, message) 
        VALUES ('$customer_id', '$message')";

if ($conn->query($sql) === TRUE) {
    header("Location: dashboards/customer_dashboard.php?feedback=success");
    exit();
} else {
    echo "Error: " . $conn->error;
}
?>