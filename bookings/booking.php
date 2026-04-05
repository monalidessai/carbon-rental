<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['login_customer'])) {
    header('Location: ../auth/login.php?msg=Please+login+to+book');
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

// Get car ID from URL
$car_id = isset($_GET['car']) ? (int)$_GET['car'] : 0;

if ($car_id <= 0) {
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

// Fetch car details
$car = null;
$car_image_url = '';
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT car_id, car_name, car_nameplate, car_type, car_status, non_ac_price_per_day, ac_surcharge_per_day FROM cars WHERE car_id = ? AND car_status = 'Available'");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $car = $result->fetch_assoc();
        // Get the car image from the map
        $car_image_url = $image_map[$car['car_name']] ?? 'https://via.placeholder.com/600x300/5B2333/F7F4F3?text=' . urlencode($car['car_name']);
    }
    $stmt->close();
}

if (!$car) {
    header('Location: ../index.php?msg=Car+not+available');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $source = trim($_POST['source'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $ac_selected = isset($_POST['ac_selected']) ? 1 : 0;
    
    // Validate dates
    if ($start_date && $end_date) {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        
        if ($end >= $start && $source && $destination) {
            // Calculate number of days
            $days = ceil(($end - $start) / 86400) + 1; // +1 to include both start and end day
            
            // Calculate price - using actual AC surcharge from database
            $base_price = (float)$car['non_ac_price_per_day'];
            $ac_surcharge = $ac_selected ? (float)$car['ac_surcharge_per_day'] : 0.00;
            $price_per_day = $base_price + $ac_surcharge;
            $total_amount = $price_per_day * $days;
            
            // Find random available driver
            $driver_id = null;
            if (isset($conn) && $conn instanceof mysqli) {
                $driver_stmt = $conn->prepare("SELECT driver_id FROM driver WHERE driver_availability = 'yes' ORDER BY RAND() LIMIT 1");
                $driver_stmt->execute();
                $driver_result = $driver_stmt->get_result();
                if ($driver_result->num_rows > 0) {
                    $driver_data = $driver_result->fetch_assoc();
                    $driver_id = $driver_data['driver_id'];
                    
                    // Mark driver as unavailable
                    $update_driver_stmt = $conn->prepare("UPDATE driver SET driver_availability = 'no' WHERE driver_id = ?");
                    $update_driver_stmt->bind_param("i", $driver_id);
                    $update_driver_stmt->execute();
                    $update_driver_stmt->close();
                }
                $driver_stmt->close();
            }
            
            // Save booking to database
            if (isset($conn) && $conn instanceof mysqli && $customer_id) {
                $stmt = $conn->prepare("INSERT INTO bookings (customer_id, car_id, driver_id, start_date, end_date, source, destination, charge_type, ac_selected, price_per_day, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'days', ?, ?, ?, 'active')");
                $stmt->bind_param("iiisssiddd", $customer_id, $car_id, $driver_id, $start_date, $end_date, $source, $destination, $ac_selected, $price_per_day, $total_amount);
                
                if ($stmt->execute()) {
                    $booking_id = $conn->insert_id;
                    
                    // Update car status to Booked
                    $update_stmt = $conn->prepare("UPDATE cars SET car_status = 'Booked' WHERE car_id = ?");
                    $update_stmt->bind_param("i", $car_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $stmt->close();
                    
                    // Redirect to confirmation page
                    header("Location: booking_confirmation.php?booking_id=$booking_id");
                    exit;
                } else {
                    $error = "Failed to save booking. Please try again.";
                }
                $stmt->close();
            } else {
                $error = "Database connection error.";
            }
        } else {
            $error = "End date must be equal to or greater than start date and both locations must be filled.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Car - Carbon Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
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
        
        // Date validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const form = document.getElementById('booking-form');
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            startDateInput.setAttribute('min', today);
            endDateInput.setAttribute('min', today);
            
            // Update end date minimum when start date changes
            startDateInput.addEventListener('change', function() {
                endDateInput.setAttribute('min', this.value);
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (endDate < startDate) {
                    e.preventDefault();
                    alert('End date must be equal to or greater than start date.');
                    return false;
                }
            });
        });
    </script>
</head>
<body class="bg-page min-h-screen flex flex-col text-text-dark">
    <!-- Header & Navigation -->
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

    <!-- Main Content -->
    <main class="flex-grow py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-primary mb-8 text-center">Book Your Car</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Car Image and Details -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-accent-light">
                    <div class="h-64 overflow-hidden rounded-lg mb-6">
                        <img src="<?= e($car_image_url) ?>" alt="<?= e($car['car_name']) ?>" class="w-full h-full object-cover rounded-lg transition-transform duration-500 hover:scale-110">
                    </div>
                    <h2 class="text-3xl font-bold text-text-dark mb-2"><?= e($car['car_name']) ?></h2>
                    <p class="text-sm text-gray-500 mb-2">License: <span class="font-mono font-semibold"><?= e($car['car_nameplate']) ?></span></p>
                    <p class="text-gray-600 mb-4">Type: <?= e($car['car_type']) ?></p>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Non-AC Price:</span>
                            <span class="font-semibold text-text-dark">₹<?= number_format($car['non_ac_price_per_day'], 0) ?><span class="text-xs text-gray-500">/day</span></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">AC Surcharge:</span>
                            <span class="font-semibold text-text-dark">+₹<?= number_format($car['ac_surcharge_per_day'], 0) ?><span class="text-xs text-gray-500">/day</span></span>
                        </div>
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Driver Service</span>
                            <span class="font-semibold">Included</span>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Form -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-accent-light">
                    <h2 class="text-2xl font-bold text-primary mb-6">Booking Details</h2>
                    <form id="booking-form" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-text-dark mb-2">Start Date</label>
                                <input type="date" id="start_date" name="start_date" required class="block w-full p-3 border border-accent-light rounded-lg shadow-sm focus:ring-primary focus:border-primary transition duration-150">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-text-dark mb-2">End Date</label>
                                <input type="date" id="end_date" name="end_date" required class="block w-full p-3 border border-accent-light rounded-lg shadow-sm focus:ring-primary focus:border-primary transition duration-150">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-text-dark mb-2">Source</label>
                                <input type="text" name="source" placeholder="Pickup location" required class="block w-full p-3 border border-accent-light rounded-lg shadow-sm focus:ring-primary focus:border-primary transition duration-150">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-text-dark mb-2">Destination</label>
                                <input type="text" name="destination" placeholder="Drop-off location" required class="block w-full p-3 border border-accent-light rounded-lg shadow-sm focus:ring-primary focus:border-primary transition duration-150">
                            </div>
                        </div>
                        <div class="pt-4 border-t border-accent-light">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="ac_selected" value="1" class="w-4 h-4 text-primary border-accent-light rounded focus:ring-primary">
                                <span class="text-sm font-medium text-text-dark">Air Conditioning (+₹<?= number_format($car['ac_surcharge_per_day'], 0) ?> surcharge per day)</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-2">Non-AC is default. Check this box to add AC for an additional ₹<?= number_format($car['ac_surcharge_per_day'], 0) ?> per day.</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center gap-2 text-blue-700 mb-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold">Driver Service Included</span>
                            </div>
                            <p class="text-sm text-blue-600">A professional driver will be automatically assigned to your booking at no extra cost.</p>
                        </div>
                        <button type="submit" class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-bold rounded-lg shadow-xl text-page bg-primary hover:bg-primary/90 transition duration-200 transform hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            Book Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-primary text-page py-10 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-xl font-extrabold text-page tracking-wider block">Carbon Rental</span>
                <p class="text-sm mt-1 text-accent-light">&copy; 2025 Carbon Rental System. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>