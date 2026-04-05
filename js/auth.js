// auth.js - Updated version with better role management
let currentUser = null;
let currentUserRole = null;

// Initialize authentication
function initAuth() {
    // Check if user is logged in (from sessionStorage or localStorage)
    const savedUser = sessionStorage.getItem('currentUser') || localStorage.getItem('currentUser');
    const savedRole = sessionStorage.getItem('userRole') || localStorage.getItem('userRole');
    
    if (savedUser && savedRole) {
        currentUser = JSON.parse(savedUser);
        currentUserRole = savedRole;
        console.log('Auth initialized - User:', currentUser, 'Role:', currentUserRole);
    }
}

// Get current user role
function getCurrentUserRole() {
    console.log('Getting user role:', currentUserRole);
    return currentUserRole;
}

// Get current user
function getCurrentUser() {
    return currentUser;
}

// Login function
function login(email, password, rememberMe = false) {
    console.log('Login attempt for:', email);
    
    // Hardcoded owner credentials (for demo)
    const ownerEmail = 'owner@carbonrental.com';
    const ownerPassword = 'owner123';
    
    // Hardcoded customer credentials (for demo)
    const customerEmail = 'customer@carbonrental.com';
    const customerPassword = 'customer123';
    
    if (email === ownerEmail && password === ownerPassword) {
        currentUser = { 
            email: ownerEmail, 
            name: 'System Owner',
            id: 'owner_001'
        };
        currentUserRole = 'owner';
        
        // Store login session
        if (rememberMe) {
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            localStorage.setItem('userRole', currentUserRole);
        } else {
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            sessionStorage.setItem('userRole', currentUserRole);
        }
        
        console.log('Owner login successful');
        return { success: true, role: 'owner', redirect: 'owner_dashboard.php' };
    }
    else if (email === customerEmail && password === customerPassword) {
        currentUser = { 
            email: customerEmail, 
            name: 'Demo Customer',
            id: 'customer_001'
        };
        currentUserRole = 'customer';
        
        // Store login session
        if (rememberMe) {
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            localStorage.setItem('userRole', currentUserRole);
        } else {
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            sessionStorage.setItem('userRole', currentUserRole);
        }
        
        console.log('Customer login successful');
        return { success: true, role: 'customer', redirect: 'customer_dashboard.php' };
    }
    else {
        console.log('Login failed - invalid credentials');
        return { success: false, message: 'Invalid email or password' };
    }
}

// Logout function
function logout() {
    console.log('Logging out user:', currentUser);
    currentUser = null;
    currentUserRole = null;
    sessionStorage.removeItem('currentUser');
    sessionStorage.removeItem('userRole');
    localStorage.removeItem('currentUser');
    localStorage.removeItem('userRole');
}

// Check if user is logged in
function isLoggedIn() {
    const loggedIn = currentUser !== null;
    console.log('isLoggedIn:', loggedIn);
    return loggedIn;
}

// Check if user is owner
function isOwner() {
    const isOwner = currentUserRole === 'owner';
    console.log('isOwner:', isOwner);
    return isOwner;
}

// Check if user is customer
function isCustomer() {
    const isCustomer = currentUserRole === 'customer';
    console.log('isCustomer:', isCustomer);
    return isCustomer;
}

// Get all bookings (mock data for demo)
function getAllBookings() {
    return [
        {
            id: 1,
            customerName: 'John Doe',
            customerEmail: 'customer@carbonrental.com',
            phone: '+1234567890',
            carName: 'Honda City',
            carType: 'Sedan',
            hasAC: true,
            dateFrom: '2024-01-15',
            dateTo: '2024-01-20'
        },
        {
            id: 2,
            customerName: 'Jane Smith',
            customerEmail: 'jane@example.com',
            phone: '+0987654321',
            carName: 'Toyota Innova',
            carType: 'SUV',
            hasAC: true,
            dateFrom: '2024-01-18',
            dateTo: '2024-01-25'
        }
    ];
}

// Initialize auth when script loads
initAuth();