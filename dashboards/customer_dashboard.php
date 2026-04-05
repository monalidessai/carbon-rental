<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'customer') {
    echo "Access Denied. You must be logged in as a customer to access this page.";
    echo "<br>Redirecting to home page...";
    header("Refresh: 3; url=../index.php");
    exit();
}

// Get customer information
$customer_id = $_SESSION['customer_id'] ?? null;
$customerName = $_SESSION['customer_name'] ?? 'Customer';
$customer_email = $_SESSION['user_email'] ?? $_SESSION['login_customer'] ?? '';

// Fetch customer's booking statistics
$total_bookings = 0;
$active_rentals = 0;
$customer_bookings = [];
$active_bookings = [];

if (isset($conn) && $conn instanceof mysqli && $customer_id) {
    // Get total bookings count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $total_bookings = $data['total'];
    }
    $stmt->close();

    // Get active rentals count (bookings with status 'active' and end_date >= today)
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM bookings WHERE customer_id = ? AND status = 'active' AND end_date >= CURDATE()");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $active_rentals = $data['active'];
    }
    $stmt->close();

    // Get all customer bookings with car and driver details (for recent bookings)
    $stmt = $conn->prepare("
        SELECT 
            b.bookid, b.start_date, b.end_date, b.source, b.destination, 
            b.ac_selected, b.total_amount, b.status, b.created_at,
            c.car_name, c.car_type, c.car_nameplate,
            d.driver_name, d.driver_number
        FROM bookings b
        JOIN cars c ON b.car_id = c.car_id
        LEFT JOIN driver d ON b.driver_id = d.driver_id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $customer_bookings[] = $row;
    }
    $stmt->close();

    // Get active and upcoming bookings separately for the active rentals section
    $stmt = $conn->prepare("
        SELECT 
            b.bookid, b.start_date, b.end_date, b.source, b.destination, 
            b.ac_selected, b.total_amount, b.status, b.created_at,
            c.car_name, c.car_type, c.car_nameplate,
            d.driver_name, d.driver_number
        FROM bookings b
        JOIN cars c ON b.car_id = c.car_id
        LEFT JOIN driver d ON b.driver_id = d.driver_id
        WHERE b.customer_id = ? AND b.status = 'active' AND b.end_date >= CURDATE()
        ORDER BY b.start_date ASC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $active_bookings[] = $row;
    }
    $stmt->close();
}

// Handle cancel booking if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    if (isset($conn) && $conn instanceof mysqli && $customer_id) {
        // Get booking details to free up car and driver
        $stmt = $conn->prepare("
            SELECT b.car_id, b.driver_id 
            FROM bookings b 
            WHERE b.bookid = ? AND b.customer_id = ? AND b.status = 'active'
        ");
        $stmt->bind_param("ii", $booking_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking_data = $result->fetch_assoc();
            $car_id = $booking_data['car_id'];
            $driver_id = $booking_data['driver_id'];
            
            // Update booking status to cancelled
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE bookid = ? AND customer_id = ?");
            $update_stmt->bind_param("ii", $booking_id, $customer_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Mark car as available again
            $car_stmt = $conn->prepare("UPDATE cars SET car_status = 'Available' WHERE car_id = ?");
            $car_stmt->bind_param("i", $car_id);
            $car_stmt->execute();
            $car_stmt->close();
            
            // Mark driver as available again if there was a driver
            if ($driver_id) {
                $driver_stmt = $conn->prepare("UPDATE driver SET driver_availability = 'yes' WHERE driver_id = ?");
                $driver_stmt->bind_param("i", $driver_id);
                $driver_stmt->execute();
                $driver_stmt->close();
            }
            
            $success_message = "Booking successfully cancelled!";
            
            // Refresh the page to show updated data
            header("Location: customer_dashboard.php?success=1");
            exit();
        }
        $stmt->close();
    }
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Carbon Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'page': '#F7F4F3',
                        'primary': '#5B2333',
                        'accent-light': '#D6C8CC',
                        'text-dark': '#333333',
                    },
                },
            }
        }
    </script>
</head>
<body class="bg-page min-h-screen flex flex-col text-text-dark">
    <!-- Header -->
    <header class="bg-primary shadow-lg sticky top-0 z-40">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="../index.php" class="text-2xl font-extrabold text-page tracking-wider">
                        Carbon<span class="font-light">Rental</span>
                    </a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="../index.php" class="text-page hover:bg-accent-light hover:text-primary px-3 py-2 rounded-lg text-sm font-medium transition duration-150">Home</a>
                        <span class="text-page mr-4 text-green-300 font-semibold">Welcome, <?= e($customerName) ?></span>
                        <a href="../auth/logout.php" class="bg-red-500 text-page hover:bg-red-600 px-4 py-2 rounded-full text-sm font-bold shadow-md transition duration-200">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Dashboard Content -->
    <main class="flex-grow py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-primary mb-8 text-center">My Dashboard</h1>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    Booking successfully cancelled!
                </div>
            <?php endif; ?>
            
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">My Bookings</h3>
                    <p class="text-3xl font-bold text-text-dark"><?= $total_bookings ?></p>
                    <p class="text-sm text-gray-500 mt-2">Total bookings made</p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Active Rentals</h3>
                    <p class="text-3xl font-bold text-text-dark"><?= $active_rentals ?></p>
                    <p class="text-sm text-gray-500 mt-2">Currently rented</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <a href="../index.php#featured" class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition duration-300 border border-accent-light">
                    <div class="text-primary text-3xl mb-3">🚗</div>
                    <h3 class="font-semibold text-text-dark">Book a Car</h3>
                    <p class="text-sm text-gray-600 mt-2">Find your next ride</p>
                </a>
                
                <a href="#bookings" class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition duration-300 border border-accent-light">
                    <div class="text-primary text-3xl mb-3">📝</div>
                    <h3 class="font-semibold text-text-dark">My Bookings</h3>
                    <p class="text-sm text-gray-600 mt-2">View all bookings</p>
                </a>
            </div>


<!-- ✅ ADD FEEDBACK HERE -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-12">
    <h2 class="text-2xl font-bold text-primary mb-4">Give Feedback</h2>

    <form action="../submit_feedback.php" method="POST">
        <input type="hidden" name="customer_id" value="<?= $customer_id ?>">

        <textarea name="message" class="w-full border rounded-lg p-2" rows="4" placeholder="Write your feedback..." required></textarea><br><br>

        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
            Submit Feedback
        </button>
    </form>
</div>

            <!-- Active Rentals Section -->
            <?php if (!empty($active_bookings)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-bold text-primary mb-6">Active Rentals</h2>
                <div class="space-y-4">
                    <?php foreach ($active_bookings as $booking): ?>
                        <?php
                        // Calculate days
                        $start_date = new DateTime($booking['start_date']);
                        $end_date = new DateTime($booking['end_date']);
                        $today = new DateTime();
                        $days = $start_date->diff($end_date)->days + 1;
                        
                        // Determine if it's upcoming or currently active
                        if ($today < $start_date) {
                            $status_text = 'Upcoming';
                            $status_color = 'bg-blue-100 text-blue-800';
                        } else {
                            $status_text = 'Active';
                            $status_color = 'bg-green-100 text-green-800';
                        }
                        ?>
                        
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800"><?= e($booking['car_name']) ?> - <?= e($booking['car_type']) ?></h3>
                                    <p class="text-gray-600 text-sm">
                                        <?= e($booking['car_nameplate']) ?> • 
                                        <?= $booking['ac_selected'] ? 'Air Conditioned' : 'Non-AC' ?> • 
                                        <?= $days ?> <?= $days == 1 ? 'Day' : 'Days' ?>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?= date('M j, Y', strtotime($booking['start_date'])) ?> - <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                    </p>
                                    <?php if ($booking['driver_name']): ?>
                                    <p class="text-sm text-green-600 mt-1">
                                        🚗 Driver: <?= e($booking['driver_name']) ?> (<?= e($booking['driver_number']) ?>)
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                                        <?= $status_text ?>
                                    </span>
                                    <p class="text-lg font-bold text-primary mt-2">₹<?= number_format($booking['total_amount'], 2) ?></p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Pickup Location</p>
                                    <p class="font-medium"><?= e($booking['source']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Drop-off Location</p>
                                    <p class="font-medium"><?= e($booking['destination']) ?></p>
                                </div>
                            </div>
                            
                            <!-- Cancel Booking Form -->
                            <form method="POST" class="mt-4 pt-4 border-t border-gray-200">
                                <input type="hidden" name="booking_id" value="<?= $booking['bookid'] ?>">
                                <button type="submit" name="cancel_booking" onclick="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')" 
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition duration-200">
                                    Cancel Booking
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Bookings Section -->
            <div id="bookings" class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-primary mb-6">My Recent Bookings</h2>
                
                <?php if (empty($customer_bookings)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg mb-4">You haven't made any bookings yet.</p>
                        <a href="../index.php#featured" class="inline-block px-6 py-2 bg-primary text-page rounded-lg hover:bg-primary/90 transition duration-200">
                            Book Your First Car
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($customer_bookings as $booking): ?>
                            <?php
                            // Calculate days and determine status
                            $start_date = new DateTime($booking['start_date']);
                            $end_date = new DateTime($booking['end_date']);
                            $today = new DateTime();
                            $days = $start_date->diff($end_date)->days + 1;
                            
                            // Determine status with priority
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
                            
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800"><?= e($booking['car_name']) ?> - <?= e($booking['car_type']) ?></h3>
                                        <p class="text-gray-600 text-sm">
                                            <?= e($booking['car_nameplate']) ?> • 
                                            <?= $booking['ac_selected'] ? 'Air Conditioned' : 'Non-AC' ?> • 
                                            <?= $days ?> <?= $days == 1 ? 'Day' : 'Days' ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?= date('M j, Y', strtotime($booking['start_date'])) ?> - <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                        </p>
                                        <?php if ($booking['driver_name']): ?>
                                        <p class="text-sm text-green-600 mt-1">
                                            🚗 Driver: <?= e($booking['driver_name']) ?> (<?= e($booking['driver_number']) ?>)
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                                            <?= $status_text ?>
                                        </span>
                                        <p class="text-lg font-bold text-primary mt-2">₹<?= number_format($booking['total_amount'], 2) ?></p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500">Pickup Location</p>
                                        <p class="font-medium"><?= e($booking['source']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Drop-off Location</p>
                                        <p class="font-medium"><?= e($booking['destination']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-primary text-page py-10 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-xl font-extrabold text-page tracking-wider block">Carbon Rental</span>
            <p class="text-sm mt-1 text-accent-light">&copy; 2025 Carbon Rental System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>