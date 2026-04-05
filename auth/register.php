<?php
session_start();
include '../includes/db_connect.php';
// Check if $conn is valid after inclusion
if (!isset($conn) || $conn->connect_error) {
    // If connection failed, set error and exit gracefully
    $message = "Database connection failed. Check your connection settings.";
    $toastClass = "#dc3545"; // Red for error
    // Skip to HTML output to show the error toast
} else 
{
$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $username = strtolower($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = strtolower($_POST['email']);
    $password = $_POST['password'];
    $phone = "+91" . trim($_POST['phone']); // Auto-add +91 prefix
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    $checkEmailStmt = $conn->prepare("SELECT customer_email FROM customers WHERE customer_email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        $message = "Email ID already exists";
        $toastClass = "#007bff";
    } else 
    {
        // Updated to include phone number with correct column name
        if (!$stmt = $conn->prepare("INSERT INTO customers (customer_username, customer_name, customer_email, customer_password, customer_phone_number) VALUES (?, ?, ?, ?, ?)")) 
            {
                 $message = "Error preparing insert statement: " . $conn->error;
                 $toastClass = "#dc3545";
            } else 
            {
                $stmt->bind_param("sssss", $username, $fullname, $email, $hashed_password, $phone);

        
                if ($stmt->execute()) {
                    // Store user data in the session for use on the main page.
                    $_SESSION['customer_id'] = $conn->insert_id; // Get the ID of the newly inserted customer
                    $_SESSION['customer_name'] = $fullname; // Store the full name
                    $_SESSION['customer_email'] = $email; // Store email
                    $_SESSION['customer_phone'] = $phone; // Store phone number
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_role'] = 'customer'; // AUTO-SET ROLE AS CUSTOMER
                    $_SESSION['login_customer'] = $email;
                
                    // Also set JavaScript auth session
                    echo "<script>
                        sessionStorage.setItem('currentUser', JSON.stringify({
                            email: '$email',
                            name: '$fullname',
                            phone: '$phone',
                            id: '{$_SESSION['customer_id']}'
                        }));
                        sessionStorage.setItem('userRole', 'customer');
                    </script>";
                    
                    // Redirect the user to the main page
                    header('Location: ../index.php');
                    exit(); // Crucial: Stop script execution after redirect
                } else {
                    // Keep the error handling block as is
                    $message = "Error: " . $stmt->error;
                    $toastClass = "#dc3545";
                }

        $stmt->close();
        }
    }

    $checkEmailStmt->close();
    $conn->close();
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/auth.css">
    <title>Registration</title>
</head>
<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true"
                style="background-color: <?php echo $toastClass; ?>;">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <form method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px;
            box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px,
            rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row text-center">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: green;"></i>
                <h5 class="p-4" style="font-weight: 700;">Create Customer Account</h5>
            </div>
            <div class="mb-2">
                <label for="username"><i class="fa fa-user"></i> User Name</label>
                <input type="text" name="username" id="username" class="form-control" style="text-transform: lowercase;" required>
            </div>
            <div class="mb-2 mt-2">
                <label for="fullname"><i class="fa fa-user"></i> Full Name</label>
                <input type="text" name="fullname" id="fullname" class="form-control" required>
            </div>
            <div class="mb-2 mt-2">
                <label for="email"><i class="fa fa-envelope"></i> Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-2 mt-2">
                <label for="phone"><i class="fa fa-phone"></i> Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text">+91</span>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="1234567890" maxlength="10" required>
                </div>
                <small class="text-muted">Enter 10-digit phone number without +91</small>
            </div>
            <div class="mb-2 mt-2">
                <label for="password"><i class="fa fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
                <small class="text-muted">Use a strong password with letters, numbers, and symbols</small>
            </div>
            <div class="mb-2 mt-3">
                <button type="submit" class="btn btn-success bg-success w-100" style="font-weight: 600;">Create Customer Account</button>
            </div>
            <div class="mb-2 mt-4">
                <p class="text-center" style="font-weight: 600; color: navy;">I have an Account <a href="login.php" style="text-decoration: none;">Login</a></p>
            </div>
            <div class="text-center mt-3">
                <small class="text-muted">By creating an account, you agree to our Terms of Service</small>
            </div>
        </form>
    </div>
    <script>
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 4000 });
        });
        toastList.forEach(toast => toast.show());
        
        // Phone number validation - only allow 10 digits
        document.getElementById('phone').addEventListener('input', function(e) {
            // Remove any non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            
            e.target.value = value;
        });
        
        // Form validation for phone number
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('phone');
            if (phoneInput.value.length !== 10) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number');
                phoneInput.focus();
            }
        });
        
        // Password strength indicator (optional enhancement)
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = checkPasswordStrength(password);
            // You can add visual feedback here if needed
        });
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            return strength;
        }
    </script>
</body>
</html>