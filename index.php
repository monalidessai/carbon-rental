<?php
session_start(); // Start the session system

// Users are allowed to browse regardless of role
// No redirect needed - both owners and customers can browse the vehicles

// If no session found, continue to display the home page
require 'includes/db_connect.php'; // must create $conn (mysqli)

// Get user information from session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['login_customer']);
$userRole = $_SESSION['user_role'] ?? null;
$userName = $_SESSION['user_name'] ?? $_SESSION['customer_name'] ?? $_SESSION['login_customer'] ?? '';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['login_customer'] ?? '';


// Calculate user initial for the profile icon
$userInitial = '';
if ($isLoggedIn && !empty($userName)) {
    // If user has a name and is not an "Owner" role (which might just display 'Owner'), calculate the initial.
    if ($userRole === 'customer' || (empty($userRole) && !str_contains(strtolower($userName), 'owner'))) {
        $userInitial = strtoupper(substr($userName, 0, 1));
    } else if ($userRole === 'owner') {
        $userInitial = 'O'; // Owner initial
    }
}

// Define the complete image URL map to be used for both DB and fallback
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

$hero_image_url = 'https://i.postimg.cc/tg4zPhP4/Rent-Car.jpg';


// Try to fetch cars from DB; fallback to inline array if DB not available
$cars = [];

if (isset($conn) && $conn instanceof mysqli) {
    // Assuming the database contains all cars listed by the user.
    // Fetch all cars and dynamically assign image URL based on name
    $res = $conn->query("SELECT car_id, car_name, car_nameplate, car_type, car_status, non_ac_price_per_day, ac_surcharge_per_day FROM cars WHERE 1");
    if ($res && $res->num_rows > 0) {
        $db_cars = $res->fetch_all(MYSQLI_ASSOC);
        
        foreach ($db_cars as $r) {
             // Assign image URL based on car name (or use a placeholder if not found)
            $image_url = $image_map[$r['car_name']] ?? 'https://via.placeholder.com/640x360?text=Car+Image+Unavailable';

            $cars[] = [
                'id' => (int)$r['car_id'],
                'name' => $r['car_name'],
                'nameplate' => $r['car_nameplate'],
                'type' => $r['car_type'],
                'status' => $r['car_status'],
                'non_ac_price' => (float)$r['non_ac_price_per_day'],
                'ac_surcharge' => (float)$r['ac_surcharge_per_day'],
                'image_url' => $image_url, // Include the image URL
            ];
        }
    }
}

if (empty($cars)) {
    // FALLBACK DATA: Use the full list of cars with images and dummy data
    $cars = [
        // Existing 5 cars
        ['id'=>1, 'name'=>'Honda City', 'nameplate'=>'MH-12-AB-1234', 'type'=>'Sedan', 'status'=>'Available', 'non_ac_price'=>1200.00, 'ac_surcharge'=>300.00, 'image_url'=>$image_map['Honda City']],
        ['id'=>2, 'name'=>'Toyota Innova', 'nameplate'=>'DL-01-XY-5678', 'type'=>'SUV', 'status'=>'Available', 'non_ac_price'=>2000.00, 'ac_surcharge'=>500.00, 'image_url'=>$image_map['Toyota Innova']],
        ['id'=>3, 'name'=>'Maruti Swift', 'nameplate'=>'KA-03-DE-9012', 'type'=>'Hatchback', 'status'=>'Available', 'non_ac_price'=>800.00, 'ac_surcharge'=>200.00, 'image_url'=>$image_map['Maruti Swift']],
        ['id'=>4, 'name'=>'Hyundai Creta', 'nameplate'=>'TN-09-FG-3456', 'type'=>'SUV', 'status'=>'Booked', 'non_ac_price'=>1800.00, 'ac_surcharge'=>450.00, 'image_url'=>$image_map['Hyundai Creta']],
        ['id'=>5, 'name'=>'Mahindra XUV500', 'nameplate'=>'GJ-01-HI-7890', 'type'=>'SUV', 'status'=>'Available', 'non_ac_price'=>2200.00, 'ac_surcharge'=>550.00, 'image_url'=>$image_map['Mahindra XUV500']],

        // New cars added to the fallback list with sample data
        ['id'=>6, 'name'=>'Swift Dzire', 'nameplate'=>'UP-14-GH-1001', 'type'=>'Sedan', 'status'=>'Available', 'non_ac_price'=>1000.00, 'ac_surcharge'=>250.00, 'image_url'=>$image_map['Swift Dzire']],
        ['id'=>7, 'name'=>'Hyundai i20', 'nameplate'=>'HR-26-IJ-2002', 'type'=>'Hatchback', 'status'=>'Available', 'non_ac_price'=>900.00, 'ac_surcharge'=>200.00, 'image_url'=>$image_map['Hyundai i20']],
        ['id'=>8, 'name'=>'Kia Seltos', 'nameplate'=>'MH-03-KL-3003', 'type'=>'SUV', 'status'=>'Available', 'non_ac_price'=>1900.00, 'ac_surcharge'=>400.00, 'image_url'=>$image_map['Kia Seltos']],
        ['id'=>9, 'name'=>'Isuzu V-Cross', 'nameplate'=>'CH-01-MN-4004', 'type'=>'Pickup', 'status'=>'Available', 'non_ac_price'=>3000.00, 'ac_surcharge'=>600.00, 'image_url'=>$image_map['Isuzu V-Cross']],
        ['id'=>10, 'name'=>'Hyundai Tucson', 'nameplate'=>'DL-05-OP-5005', 'type'=>'SUV', 'status'=>'Booked', 'non_ac_price'=>2500.00, 'ac_surcharge'=>500.00, 'image_url'=>$image_map['Hyundai Tucson']],
        ['id'=>11, 'name'=>'Renault Triber', 'nameplate'=>'RJ-01-QR-6006', 'type'=>'MUV', 'status'=>'Available', 'non_ac_price'=>1300.00, 'ac_surcharge'=>300.00, 'image_url'=>$image_map['Renault Triber']],
        ['id'=>12, 'name'=>'Kia Carens', 'nameplate'=>'TS-09-ST-7007', 'type'=>'MUV', 'status'=>'Available', 'non_ac_price'=>1700.00, 'ac_surcharge'=>400.00, 'image_url'=>$image_map['Kia Carens']],
    ];
}

$carInventoryPayload = array_map(function($car) {
    // Ensure the image_url is included for the JS payload
    $image_url = $car['image_url'] ?? 'https://via.placeholder.com/640x360?text=Car+Image+Unavailable';
    
    return [
        'id' => $car['id'],
        'name' => $car['name'],
        'type' => $car['type'],
        'reg' => $car['nameplate'],
        'status' => $car['status'],
        'price' => $car['non_ac_price'],
        'ac_surcharge' => $car['ac_surcharge'],
        'image_url' => $image_url, // Pass the image URL to JavaScript
    ];
}, $cars);

// helper to escape output
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carbon Car Rental - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
        import { getFirestore, collection, addDoc, onSnapshot } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js';
        
        // Initialize Firebase using global variables
        if (typeof __app_id !== 'undefined' && typeof __firebase_config !== 'undefined') {
            const firebaseConfig = JSON.parse(__firebase_config);
            const app = initializeApp(firebaseConfig);
            window.firebaseApp = app;
            window.firebaseAuth = getAuth(app);
            window.firebaseDb = getFirestore(app);
            
            // Set up onAuthStateChanged to track user ID
            onAuthStateChanged(window.firebaseAuth, (user) => {
                if (user) {
                    window.currentFirebaseUserId = user.uid;
                } else {
                    window.currentFirebaseUserId = null;
                }
            });
            
            // Make Firestore functions available globally
            window.addBookingToFirestore = async function(bookingData) {
                try {
                    const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
                    const bookingsRef = collection(window.firebaseDb, `artifacts/${appId}/public/data/bookings`);
                    const docRef = await addDoc(bookingsRef, bookingData);
                    return { success: true, id: docRef.id };
                } catch (error) {
                    console.error('Error adding booking to Firestore:', error);
                    return { success: false, error: error.message };
                }
            };
        } else {
            console.warn('Firebase configuration not found. Using fallback mode.');
        }
    </script>
    <script src="js/auth.js"></script>
    <style>
        /* Apply the custom font globally */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Smooth scrolling for the whole page */
        html {
            scroll-behavior: smooth;
        }
    </style>
    <script>
        // Custom Tailwind Configuration to map the requested colors
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'page': '#F7F4F3',
                        'primary': '#5B2333',
                        'accent-light': '#D6C8CC', /* Slightly lighter shade for borders/hover */
                        'text-dark': '#333333',
                    },
                },
            }
        }

        // Enhanced smooth scrolling with offset for fixed header
        function smoothScrollTo(targetId) {
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        }

        // Add click event listeners to navigation links
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    smoothScrollTo(targetId);
                });
            });
        });

        // Update the existing navigate function to use smooth scrolling
        function navigate(sectionId) {
            smoothScrollTo(sectionId);
        }

        // Car Inventory sourced from PHP (DB or fallback)
        const CAR_INVENTORY = <?php echo json_encode($carInventoryPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // NEW: User role variable for conditional rendering
        const CURRENT_USER_ROLE = <?php echo json_encode($userRole ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // Render cars dynamically
        function renderCars() {
            const container = document.getElementById('vehicle-cards');
            if (!container) return;
            
            container.innerHTML = '';
            
            CAR_INVENTORY.forEach(car => {
                const carCard = document.createElement('div');
                carCard.className = 'bg-page rounded-xl shadow-lg overflow-hidden border border-accent-light hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1';
                
                // Check login status from PHP session
                const phpIsLoggedIn = <?php echo json_encode($isLoggedIn ?? false); ?>;
                const isLoggedIn = phpIsLoggedIn;
                
                // Determine if the user is a CUSTOMER who can book
                const isCustomer = isLoggedIn && CURRENT_USER_ROLE !== 'owner';
                
                // Check if car is booked
                const isBooked = car.status === 'Booked';
                
                // Conditional button rendering logic
                let buttonHtml = '';
                if (isBooked) {
                    // Car is already booked
                    buttonHtml = `<button disabled class="w-full px-4 py-2 bg-gray-400 text-page rounded-full text-sm font-semibold cursor-not-allowed transition-all duration-200">Currently Booked</button>`;
                } else if (isCustomer) {
                    // Logged in as Customer - show Book Now button
                    buttonHtml = `<button onclick="handleBooking(${car.id})" class="w-full px-4 py-2 bg-primary text-page rounded-full text-sm font-semibold hover:bg-primary/90 transition-all duration-300 transform hover:scale-105">Book Now</button>`;
                } else if (isLoggedIn && CURRENT_USER_ROLE === 'owner') {
                    // Logged in as Owner - Hide the button, use a neutral spacer to keep card height consistent
                    buttonHtml = `<div class="w-full h-10 bg-white/0 pointer-events-none"></div>`;
                } else {
                    // Not logged in - show Login prompt
                    buttonHtml = `<a href="auth/login.php" class="w-full px-4 py-2 bg-primary text-page rounded-full text-sm font-semibold hover:bg-primary/90 transition-all duration-300 transform hover:scale-105 inline-block text-center">Login to Book</a>`;
                }


                carCard.innerHTML = `
                    <div class="h-40 overflow-hidden">
                        <img src="${car.image_url}" alt="${car.name}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-text-dark mb-2">${car.name}</h3>
                        <p class="text-sm text-gray-500 mb-1">License: <span class="font-mono font-semibold">${car.reg}</span></p>
                        <p class="text-sm mb-2">
                            <span class="inline-block px-2 py-1 ${car.status === 'Available' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'} rounded-full text-xs font-semibold">${car.status}</span>
                        </p>
                        <p class="text-gray-600 mb-4">Type: ${car.type}</p>
                        <div class="mb-3 space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Price per Day:</span>
                                <span class="font-semibold text-text-dark">₹${Number(car.price).toLocaleString()}<span class="text-xs text-gray-500">/day</span></span>
                            </div>
                        </div>
                        ${buttonHtml}
                    </div>
                `;
                
                container.appendChild(carCard);
            });
        }
        
        // Toggle dropdown menu
        function toggleUserDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('user-dropdown');
            const button = document.getElementById('user-dropdown-button');
            if (dropdown && button && !dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Setup Navigation based on login state
        function setupNavigation() {
            // Get user info from PHP session
            const phpUserRole = <?php echo json_encode($userRole ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const phpUserName = <?php echo json_encode($userName ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const phpUserInitial = <?php echo json_encode($userInitial ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const phpIsLoggedIn = <?php echo json_encode($isLoggedIn ?? false); ?>;
            
            const role = phpUserRole;
            const isLoggedIn = phpIsLoggedIn;
            const fullName = phpUserName;
            const userInitial = phpUserInitial;
            
            // Get navigation buttons
            const desktopNav = document.querySelector('.desktop-nav-buttons');
            
            // Desktop navigation button
            if (desktopNav) {
                if (isLoggedIn) {
                    // User is logged in - show dropdown button with user initial icon
                    const displayText = role === 'owner' ? 'Owner' : fullName;
                    
                    // Determine dropdown options based on role
                    let dropdownOptions = '';
                    if (role === 'owner') {
                        dropdownOptions = `
                            <a href="#featured" onclick="smoothScrollTo('featured'); toggleUserDropdown(); return false;" class="block px-4 py-2 text-sm text-text-dark hover:bg-accent-light transition-all duration-200">Browse Vehicles</a>
                            <a href="dashboards/owner_dashboard.php" class="block px-4 py-2 text-sm text-text-dark hover:bg-accent-light transition-all duration-200">Owner Dashboard</a>
                            <button onclick="handleLogout();" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-all duration-200">Sign Out</button>
                        `;
                    } else {
                        // Customer dropdown
                        dropdownOptions = `
                            <a href="#featured" onclick="smoothScrollTo('featured'); toggleUserDropdown(); return false;" class="block px-4 py-2 text-sm text-text-dark hover:bg-accent-light transition-all duration-200">Browse Vehicles</a>
                            <a href="dashboards/customer_dashboard.php" class="block px-4 py-2 text-sm text-text-dark hover:bg-accent-light transition-all duration-200">My Dashboard</a>
                            <button onclick="handleLogout();" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-all duration-200">Sign Out</button>
                        `;
                    }
                    
                    desktopNav.innerHTML = `
                        <div class="relative">
                            <button id="user-dropdown-button" onclick="toggleUserDropdown()" class="bg-page text-primary hover:bg-white px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-300 transform hover:scale-105 flex items-center gap-2">
                                <div class="w-6 h-6 bg-primary text-page rounded-full flex items-center justify-center text-xs font-bold">
                                    ${userInitial}
                                </div>
                                ${displayText}
                                <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50 border border-accent-light transition-all duration-300 transform origin-top-right">
                                <div class="py-1">
                                    ${dropdownOptions}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // User is logged out - show Sign-In button
                    desktopNav.innerHTML = `
                        <a href="auth/login.php" class="bg-page text-primary hover:bg-white px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-300 transform hover:scale-105">
                            Sign-In
                        </a>
                    `;
                }
            }
        }
        
        // Run setupNavigation on window load
        window.onload = function() {
            // Store user data if login was successful
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login_success') === '1') {
                const userData = urlParams.get('user_data');
                if (userData) {
                    try {
                        const data = JSON.parse(decodeURIComponent(userData));
                        localStorage.setItem('loggedInUser', JSON.stringify(data));
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } catch (e) {
                        console.error('Error storing user data:', e);
                    }
                }
            }
            
            // Setup navigation
            setupNavigation();
            
            // Render cars dynamically
            renderCars();
        };
        
        // Handle booking function
        async function handleBooking(carId) {
            // Check login status and role from JS globals
            const isLoggedIn = <?php echo json_encode($isLoggedIn ?? false); ?>;
            const userRole = CURRENT_USER_ROLE;

            if (!isLoggedIn) {
                showMessage('Please sign in to book a vehicle.', 'error');
                setTimeout(() => {
                    window.location.href = 'auth/login.php';
                }, 2000);
                return;
            }
            
            // Crucial check: Prevent owner from booking
            if (userRole === 'owner') {
                showMessage('Owners cannot book vehicles on this interface.', 'error');
                return;
            }

            if (!carId) {
                showMessage('Unable to determine vehicle. Please try again.', 'error');
                return;
            }
            
            // Redirect to booking page with selected vehicle id
            window.location.href = `bookings/booking.php?car=${encodeURIComponent(carId)}`;
        }
        
        // Handle logout - redirect to PHP logout handler
        function handleLogout() {
            window.location.href = 'auth/logout.php';
        }
            
        // Show message function
        function showMessage(message, type = 'info') {
            const messageBox = document.getElementById('message-box');
            if (!messageBox) return;
            
            const colors = {
                error: 'bg-red-500',
                success: 'bg-green-500',
                info: 'bg-blue-500'
            };
            
            messageBox.className = `${colors[type] || colors.info} text-page fixed top-0 left-0 right-0 p-4 text-center text-sm font-semibold z-50 transition-all duration-300 transform`;
            messageBox.textContent = message;
            messageBox.classList.remove('hidden');
            
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 3000);
        }
    </script>
</head>
<body class="bg-page min-h-screen flex flex-col text-text-dark">

    <div id="message-box" class="hidden fixed top-0 left-0 right-0 p-4 text-center text-sm font-semibold z-50 transition-all duration-300">
        </div>

    <header class="bg-primary shadow-lg sticky top-0 z-40">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-extrabold text-page tracking-wider transition-all duration-300 hover:opacity-90">
                        Carbon<span class="font-light">Rental</span>
                    </a>
                </div>

                <div class="ml-10 flex items-center space-x-6">
                    <div class="desktop-menu-links flex items-baseline space-x-4">
                        <a href="#hero" class="nav-link text-page hover:bg-accent-light hover:text-primary px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105">Home</a>
                        <a href="#featured" class="nav-link text-page hover:bg-accent-light hover:text-primary px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105">Vehicles</a>
                        <a href="#services" class="nav-link text-page hover:bg-accent-light hover:text-primary px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105">Services</a>
                    </div>
                    <div class="desktop-nav-buttons">
                        </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow">
        
        <section id="hero" class="relative overflow-hidden pt-12 pb-24 md:pt-24 md:pb-36 bg-page">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="md:grid md:grid-cols-2 md:gap-12 lg:gap-24 items-center">
                    
                    <div class="mb-10 md:mb-0">
                        <h1 class="text-5xl md:text-6xl font-extrabold leading-tight tracking-tighter text-text-dark mb-4">
                            Drive Your <span class="text-primary">Next Adventure</span>
                        </h1>
                        <p class="text-lg text-gray-600 mb-8 max-w-lg">
                            Simple, reliable, and affordable car rentals for every journey. Manage your fleet or book your ride effortlessly with Carbon Rental.
                        </p>
                        
                        <a href="#featured" class="nav-link inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-bold rounded-lg shadow-xl text-page bg-primary hover:bg-primary/90 transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            Browse Vehicles
                        </a>
                    </div>

                    <div class="order-first md:order-last flex justify-center">
                        <div class="w-full max-w-md h-64 md:h-80 rounded-2xl shadow-2xl overflow-hidden border-4 border-primary/50 transition-all duration-300 hover:shadow-3xl hover:border-primary/70">
                            <img src="<?php echo e($hero_image_url); ?>" alt="A car being rented" class="w-full h-full object-cover">
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section id="featured" class="py-16 md:py-24 bg-white shadow-inner">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-center text-primary mb-12">Featured Vehicles</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8" id="vehicle-cards">
                    </div>
            </div>
        </section>

        <section id="services" class="py-16 md:py-24 bg-page">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl font-bold text-text-dark mb-12">Why Choose Carbon Rental?</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div class="p-6 rounded-xl bg-white shadow-xl border-t-4 border-primary transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl">
                        <span class="text-primary block text-4xl mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 12V6"/><path d="M16.24 16.24l-3.53-3.53"/></svg>
                        </span>
                        <h3 class="text-xl font-semibold text-text-dark mb-3">Fast Booking</h3>
                        <p class="text-gray-600 text-sm">Find and book a car in three easy steps right from your desktop or mobile device.</p>
                    </div>

                    <div class="p-6 rounded-xl bg-white shadow-xl border-t-4 border-primary transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl">
                        <span class="text-primary block text-4xl mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        </span>
                        <h3 class="text-xl font-semibold text-text-dark mb-3">Guaranteed Safety</h3>
                        <p class="text-gray-600 text-sm">All vehicles are thoroughly inspected and sanitized before every pickup.</p>
                    </div>

                    <div class="p-6 rounded-xl bg-white shadow-xl border-t-4 border-primary transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl">
                        <span class="text-primary block text-4xl mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto"><path d="M22.7 13.5l-9-9A2 2 0 0 0 12 4H2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 1.5-3.5l9-9c.3-.3.3-.8 0-1.1s-.8-.3-1.1 0z"/><path d="M7 10h0"/></svg>
                        </span>
                        <h3 class="text-xl font-semibold text-text-dark mb-3">Best Prices</h3>
                        <p class="text-gray-600 text-sm">Transparent, competitive pricing with no hidden fees and flexible cancellation.</p>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-primary text-page py-10 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center md:flex md:justify-between md:items-center">
                
                <div class="mb-4 md:mb-0">
                    <span class="text-xl font-extrabold text-page tracking-wider block transition-all duration-300 hover:opacity-90">Carbon Rental</span>
                    <p class="text-sm mt-1 text-accent-light">&copy; 2025 Carbon Rental System. All rights reserved.</p>
                </div>

                <div class="flex justify-center space-x-6">
                    <a href="#" class="text-sm text-accent-light hover:text-page transition-all duration-300 hover:scale-105">Privacy Policy</a>
                    <a href="#" class="text-sm text-accent-light hover:text-page transition-all duration-300 hover:scale-105">Terms of Service</a>
                    <a href="#services" class="nav-link text-sm text-accent-light hover:text-page transition-all duration-300 hover:scale-105">Contact</a>
                </div>

            </div>
        </div>
    </footer>

</body>
</html>