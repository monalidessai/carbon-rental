<?php
session_start();

// Debug: Check what's in session
error_log("Owner Dashboard - Session: " . print_r($_SESSION, true));

// Check if user is logged in as owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'owner') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be logged in as an owner to access this page.</p>";
    echo "<p>Redirecting to home page in 3 seconds...</p>";
    header("Refresh: 3; url=../index.php");
    exit();
}

// Include database connection
require '../includes/db_connect.php';

// Fetch owner information
$ownerName = $_SESSION['user_name'] ?? 'Owner';

// Fetch statistics and bookings from database
$total_bookings = 0;
$active_rentals = 0;
$total_revenue = 0;
$all_bookings = [];

if (isset($conn) && $conn instanceof mysqli) {
    try {
        // Get total bookings count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $total_bookings = $data['total'];
        }
        $stmt->close();

        // Get active rentals count
        $stmt = $conn->prepare("SELECT COUNT(*) as active FROM bookings WHERE status = 'active' AND end_date >= CURDATE()");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $active_rentals = $data['active'];
        }
        $stmt->close();

        // Get total revenue
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'active' OR status = 'completed'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $total_revenue = $data['revenue'];
        }
        $stmt->close();

        // Get all bookings with customer, car, and driver details
        $stmt = $conn->prepare("
            SELECT 
                b.bookid, b.start_date, b.end_date, b.source, b.destination, 
                b.ac_selected, b.total_amount, b.status, b.created_at,
                c.car_name, c.car_type, c.car_nameplate,
                cust.customer_name, cust.customer_phone_number,
                d.driver_name, d.driver_number
            FROM bookings b
            JOIN cars c ON b.car_id = c.car_id
            JOIN customers cust ON b.customer_id = cust.customer_id
            LEFT JOIN driver d ON b.driver_id = d.driver_id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $all_bookings[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Database error in owner dashboard: " . $e->getMessage());
    }
} else {
    error_log("Database connection failed in owner dashboard");
}

function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Carbon Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Dashboard Header -->
    <header class="bg-red-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Carbon Rental - Owner Dashboard</h1>
                    <p class="text-sm opacity-90">Manage all bookings and rentals</p>
                </div>
                <div class="flex items-center gap-4">
                    <span>Welcome, <?= e($ownerName) ?></span>
                    <a href="../index.php" class="text-sm hover:underline">Home</a>
                    <a href="../auth/logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Bookings</h3>
                <p class="text-3xl font-bold text-red-800"><?= $total_bookings ?></p>
                <p class="text-sm text-gray-500 mt-2">All time bookings</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Active Rentals</h3>
                <p class="text-3xl font-bold text-green-600"><?= $active_rentals ?></p>
                <p class="text-sm text-gray-500 mt-2">Currently rented</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Revenue</h3>
                <p class="text-3xl font-bold text-blue-600">₹<?= number_format($total_revenue, 2) ?></p>
                <p class="text-sm text-gray-500 mt-2">From all bookings</p>
            </div>
        </div>

        <!-- All Bookings Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">All Bookings</h2>
                <p class="text-gray-600">View and manage all customer bookings</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AC/Non-AC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_bookings)): ?>
                            <tr>
                                <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No bookings found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_bookings as $booking): ?>
                                <?php
                                // Calculate days
                                $start_date = new DateTime($booking['start_date']);
                                $end_date = new DateTime($booking['end_date']);
                                $today = new DateTime();
                                $days = $start_date->diff($end_date)->days + 1;
                                
                                // Determine status
                                $status = $booking['status'];
                                $status_color = 'bg-gray-100 text-gray-800';
                                $status_text = ucfirst($status);
                                
                                if ($status === 'active') {
                                    if ($today < $start_date) {
                                        $status_text = 'Upcoming';
                                        $status_color = 'bg-blue-100 text-blue-800';
                                    } else if ($today <= $end_date) {
                                        $status_text = 'Active';
                                        $status_color = 'bg-green-100 text-green-800';
                                    } else {
                                        $status_text = 'Completed';
                                        $status_color = 'bg-gray-100 text-gray-800';
                                    }
                                } else if ($status === 'completed') {
                                    $status_text = 'Completed';
                                    $status_color = 'bg-gray-100 text-gray-800';
                                } else if ($status === 'cancelled') {
                                    $status_text = 'Cancelled';
                                    $status_color = 'bg-red-100 text-red-800';
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $booking['bookid'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= e($booking['customer_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($booking['customer_phone_number'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($booking['car_name']) ?> (<?= e($booking['car_type']) ?>)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $booking['ac_selected'] ? 'AC' : 'Non-AC' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($booking['start_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($booking['driver_name'] ?? 'Not assigned') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        ₹<?= number_format($booking['total_amount'], 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_color ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>        

        <!-- ✅ ADD FEEDBACK SECTION HERE -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-10">
            <h2 class="text-2xl font-bold text-red-800 mb-4">Customer Feedback</h2>

            <?php
            include('../includes/db_connect.php');

            $sql = "SELECT * FROM feedback ORDER BY created_at DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>

                <div class="border-b py-2">
                    <p><strong>Customer ID:</strong> <?= $row['customer_id'] ?></p>
                    <p><strong>Message:</strong> <?= $row['message'] ?></p>
                    <p class="text-sm text-gray-500"><?= $row['created_at'] ?></p>
                </div>

            <?php endwhile; else: ?>
                <p>No feedback available.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>