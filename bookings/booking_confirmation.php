<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['login_customer'])) {
    header('Location: ../auth/login.php?mode=login');
    exit;
}

$customerName = $_SESSION['customer_name'] ?? $_SESSION['login_customer'] ?? '';
$customer_username = $_SESSION['login_customer'] ?? '';

// Fetch customer_id from database if not in session
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id && isset($conn) && $conn instanceof mysqli && $customer_username) {
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE customer_username = ?");
    $stmt->bind_param("s", $customer_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $customer_data = $result->fetch_assoc();
        $customer_id = $customer_data['customer_id'];
        $_SESSION['customer_id'] = $customer_id;
    }
    $stmt->close();
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header('Location: ../index.php');
    exit;
}

// Define the image map (same as in index.php)
$image_map = [
    'Honda City' => 'https://i.postimg.cc/Kc0NQ39P/Honda-City.jpg',
    'Toyota Innova' => 'https://i.postimg.cc/0NGGSzMJ/Toyota-Innova.jpg',
    'Maruti Swift' => 'https://i.postimg.cc/ryZ15XmZ/Maruti-Swift.jpg',
    'Hyundai Creta' => 'https://i.postimg.cc/g0KRJ1QR/Hyundai-Creta.jpg',
    'Mahindra XUV500' => 'https://i.postimg.cc/Hx2tqrTD/Mahindra-XUV500.jpg',
    'Swift Dzire' => 'https://i.postimg.cc/W3rSbs0v/Swift-Dzire.jpg',
    'Hyundai i20' => 'https://i.postimg.cc/0QfCTzP3/Hyundai-i20.jpg',
    'Kia Seltos' => 'https://i.postimg.cc/DZgc9S7K/Kia-Seltos.jpg',
    'Isuzu V-Cross' => 'https://i.postimg.cc/bw3BKY90/Isuzu-V-Cross.webp',
    'Hyundai Tucson' => 'https://i.postimg.cc/SsrGBnm4/Hyundai-Tucson.jpg',
    'Renault Triber' => 'https://i.postimg.cc/WzV1k4Zr/Renault-Triber.jpg',
    'Kia Carens' => 'https://i.postimg.cc/TwXg0g55/Kia-Carens.jpg',
];

// Fetch booking details with car, customer, and driver info
$booking = null;
$car_image_url = '';
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT b.*, c.car_name, c.car_nameplate, c.car_type, c.non_ac_price_per_day, c.ac_surcharge_per_day,
               cust.customer_name, cust.customer_email, cust.customer_phone_number,
               d.driver_name, d.driver_number
        FROM bookings b
        JOIN cars c ON b.car_id = c.car_id
        JOIN customers cust ON b.customer_id = cust.customer_id
        LEFT JOIN driver d ON b.driver_id = d.driver_id
        WHERE b.bookid = ? AND b.customer_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        // Get the car image from the map
        $car_image_url = $image_map[$booking['car_name']] ?? 'https://via.placeholder.com/400x200/5B2333/F7F4F3?text=' . urlencode($booking['car_name']);
    }
    $stmt->close();
}

if (!$booking) {
    header('Location: ../index.php?msg=Booking+not+found');
    exit;
}

// Calculate number of days
$start_date = new DateTime($booking['start_date']);
$end_date = new DateTime($booking['end_date']);
$days = $start_date->diff($end_date)->days + 1; // +1 to include both start and end day

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Carbon Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .bg-page {
                background: white !important;
            }
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
    <!-- Header & Navigation -->
    <header class="bg-primary shadow-lg sticky top-0 z-40 no-print">
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

    <!-- Main Content -->
    <main class="flex-grow py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Success Message -->
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center">
                <h1 class="text-2xl font-bold mb-2">✓ Booking Confirmed!</h1>
                <p class="text-lg">Your booking has been successfully processed.</p>
            </div>
            
            <!-- Invoice/Confirmation Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 border border-accent-light">
                <div class="text-center mb-8 pb-6 border-b border-accent-light">
                    <h2 class="text-3xl font-bold text-primary mb-2">Booking Invoice</h2>
                    <p class="text-gray-600">Booking ID: #<?= e($booking['bookid']) ?></p>
                    <p class="text-sm text-gray-500">Date: <?= date('d M Y, h:i A', strtotime($booking['created_at'])) ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Car Details -->
                    <div>
                        <h3 class="text-xl font-bold text-primary mb-4">Car Details</h3>
                        <div class="space-y-2">
                            <div class="h-48 overflow-hidden rounded-lg mb-4">
                                <img src="<?= e($car_image_url) ?>" alt="<?= e($booking['car_name']) ?>" class="w-full h-full object-cover rounded-lg">
                            </div>
                            <p class="text-lg font-semibold text-text-dark"><?= e($booking['car_name']) ?></p>
                            <p class="text-sm text-gray-600">License: <span class="font-mono font-semibold"><?= e($booking['car_nameplate']) ?></span></p>
                            <p class="text-sm text-gray-600">Type: <?= e($booking['car_type']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Booking Details -->
                    <div>
                        <h3 class="text-xl font-bold text-primary mb-4">Booking Details</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Start Date</p>
                                <p class="font-semibold text-text-dark"><?= date('d M Y', strtotime($booking['start_date'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">End Date</p>
                                <p class="font-semibold text-text-dark"><?= date('d M Y', strtotime($booking['end_date'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Duration</p>
                                <p class="font-semibold text-text-dark"><?= $days ?> <?= $days == 1 ? 'Day' : 'Days' ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Pickup Location</p>
                                <p class="font-semibold text-text-dark"><?= e($booking['source']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Drop-off Location</p>
                                <p class="font-semibold text-text-dark"><?= e($booking['destination']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Air Conditioning</p>
                                <p class="font-semibold text-text-dark"><?= $booking['ac_selected'] ? 'Yes (+₹' . number_format($booking['ac_surcharge_per_day'], 0) . '/day)' : 'No' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Driver Details -->
                <?php if ($booking['driver_name']): ?>
                <div class="mb-8 pb-6 border-b border-accent-light">
                    <h3 class="text-xl font-bold text-primary mb-4">Assigned Driver</h3>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                                D
                            </div>
                            <div>
                                <p class="font-semibold text-text-dark text-lg"><?= e($booking['driver_name']) ?></p>
                                <p class="text-gray-600">Contact: <?= e($booking['driver_number']) ?></p>
                                <p class="text-sm text-green-600 font-medium">Professional Driver Assigned</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-8 pb-6 border-b border-accent-light">
                    <h3 class="text-xl font-bold text-primary mb-4">Driver Information</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-yellow-700">Driver will be assigned shortly. You will receive driver details via SMS/Email before your trip starts.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Customer Details -->
                <div class="mb-8 pb-6 border-b border-accent-light">
                    <h3 class="text-xl font-bold text-primary mb-4">Customer Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-semibold text-text-dark"><?= e($booking['customer_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold text-text-dark"><?= e($booking['customer_email']) ?></p>
                        </div>
                        <?php if ($booking['customer_phone_number']): ?>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-semibold text-text-dark"><?= e($booking['customer_phone_number']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Price Breakdown -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-primary mb-4">Price Breakdown</h3>
                    <div class="bg-page rounded-lg p-6 space-y-3">
                        <?php
                        // Get base price (non-AC price per day) from car data
                        $base_price_per_day = (float)$booking['non_ac_price_per_day'];
                        $ac_surcharge_per_day = (float)$booking['ac_surcharge_per_day'];
                        ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Base Price per Day (Non-AC)</span>
                            <span class="font-semibold text-text-dark">₹<?= number_format($base_price_per_day, 2) ?></span>
                        </div>
                        <?php if ($booking['ac_selected']): ?>
                        <div class="flex justify-between text-sm text-green-600">
                            <span>AC Surcharge per Day</span>
                            <span class="font-semibold">+₹<?= number_format($ac_surcharge_per_day, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Price per Day (with AC)</span>
                            <span class="font-semibold text-text-dark">₹<?= number_format($booking['price_per_day'], 2) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Price per Day</span>
                            <span class="font-semibold text-text-dark">₹<?= number_format($booking['price_per_day'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Driver Service</span>
                            <span class="font-semibold">Complimentary</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Number of Days</span>
                            <span class="font-semibold text-text-dark"><?= $days ?> <?= $days == 1 ? 'Day' : 'Days' ?></span>
                        </div>
                        <div class="pt-3 border-t border-accent-light">
                            <div class="flex justify-between text-lg font-bold text-primary">
                                <span>Total Amount</span>
                                <span>₹<?= number_format($booking['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center no-print">
                    <a href="../index.php" class="px-6 py-3 bg-primary text-page rounded-lg font-semibold hover:bg-primary/90 transition duration-200 text-center">
                        Book Another Car
                    </a>
                    <button onclick="window.print()" class="px-6 py-3 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700 transition duration-200">
                        Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-primary text-page py-10 mt-12 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-xl font-extrabold text-page tracking-wider block">Carbon Rental</span>
                <p class="text-sm mt-1 text-accent-light">&copy; 2025 Carbon Rental System. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>