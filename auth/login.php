<?php
session_start();

// Include the database connection file
include '../includes/db_connect.php';

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $username_email = $_POST['username_email']; // Changed from 'email' to 'username_email'
    $password = $_POST['password'];
    $user_type = $_POST['user_type']; // 'owner' or 'customer'

    // Check if the connection object is valid
    if (!$conn || $conn->connect_error) {
        $message = "Database connection error. Please check db_connect.php.";
        $toastClass = "bg-danger";
    } else 
    {
        if ($user_type === 'customer') {
            // Customer login - using customer_username instead of email
            $sql = "SELECT customer_id, customer_username, customer_password, customer_name, customer_email, customer_phone_number
                FROM customers 
                WHERE customer_username = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $message = "DATABASE QUERY ERROR: Failed to prepare statement. MySQL Error: " . $conn->error;
                $toastClass = "bg-danger";
            } else {
                $stmt->bind_param("s", $username_email); // Using username for login
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($customer_id, $db_username, $db_password, $db_name, $db_email, $db_phone);
                    $stmt->fetch();

                    // Verify password using password_verify for hashed passwords
                    if (password_verify($password, $db_password)) {
                        $message = "Login successful";
                        $toastClass = "bg-success";
                        
                        // Set customer session variables
                        $_SESSION['user_id'] = $customer_id;
                        $_SESSION['login_customer'] = $db_username;
                        $_SESSION['customer_name'] = $db_name;
                        $_SESSION['user_role'] = 'customer';
                        $_SESSION['user_name'] = $db_name;
                        $_SESSION['user_email'] = $db_email;
                        $_SESSION['customer_phone'] = $db_phone;
                        
                        // Also set JavaScript auth session
                        echo "<script>
                            sessionStorage.setItem('currentUser', JSON.stringify({
                                email: '$db_email',
                                name: '$db_name',
                                phone: '$db_phone',
                                id: '$customer_id'
                            }));
                            sessionStorage.setItem('userRole', 'customer');
                        </script>";
                        
                        header("Location: ../index.php?login_success=1");
                        exit();
                    } else {
                        $message = "Incorrect password";
                        $toastClass = "bg-danger";
                    }
                } else {
                    $message = "Customer username not found";
                    $toastClass = "bg-warning";
                }
                $stmt->close();
            }
        } 
        elseif ($user_type === 'owner') {
            // Owner login - using owner_username column
            $sql = "SELECT owner_id, owner_username, owner_password, owner_full_name 
                    FROM owners 
                    WHERE owner_username = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $message = "DATABASE QUERY ERROR: Failed to prepare statement. MySQL Error: " . $conn->error;
                $toastClass = "bg-danger";
            } else {
                $stmt->bind_param("s", $username_email); // Using username for owner login
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($owner_id, $db_username, $db_password, $db_full_name);
                    $stmt->fetch();

                    // Verify password - CHECK BOTH HASHED AND PLAIN TEXT
                    $login_success = false;
                    
                    // First try: password_verify for hashed passwords
                    if (password_verify($password, $db_password)) {
                        $login_success = true;
                    }
                    // Second try: direct comparison for plain text (backward compatibility)
                    else if ($password === $db_password) {
                        $login_success = true;
                    }

                    if ($login_success) {
                        $message = "Login successful";
                        $toastClass = "bg-success";
                        
                        // Set owner session variables
                        $_SESSION['user_id'] = $owner_id;
                        $_SESSION['user_role'] = 'owner';
                        $_SESSION['user_name'] = $db_full_name ?: $db_username;
                        $_SESSION['owner_username'] = $db_username;
                        
                        // Also set JavaScript auth session
                        echo "<script>
                            sessionStorage.setItem('currentUser', JSON.stringify({
                                email: '$db_username',
                                name: '" . ($db_full_name ?: $db_username) . "',
                                id: '$owner_id'
                            }));
                            sessionStorage.setItem('userRole', 'owner');
                        </script>";
                        
                        header("Location: ../index.php?login_success=1");
                        exit();
                    } else {
                        $message = "Incorrect password";
                        $toastClass = "bg-danger";
                    }
                } else {
                    $message = "Owner username not found";
                    $toastClass = "bg-warning";
                }
                $stmt->close();
            }
        }
    }
    
    // Close connection
    if (isset($conn) && $conn) {
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
    <title>Login Page</title>
    <style>
        .user-type-select {
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">
     <div class="container p-5 d-flex flex-column align-items-center">
         <!-- Toast Notification Area -->
        <?php if ($message): ?>
             <div class="toast align-items-center text-white <?php echo $toastClass; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                 <div class="d-flex">
                     <div class="toast-body">
                         <?php echo $message; ?>
                     </div>
                     <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                 </div>
             </div>
        <?php endif; ?>

         <form action="" method="post" class="form-control mt-5 p-4"
             style="height:auto; width:380px; border-radius: 0.5rem; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
             <div class="row">
                 <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="text-align: center; color: #198754;"></i>
                 <h5 class="text-center p-4" style="font-weight: 700;">Login Into Your Account</h5>
             </div>
             
             <!-- User Type Selection -->
             <div class="col-mb-3 mt-3">
                 <label for="user_type"><i class="fa fa-user"></i> Login As</label>
                 <select name="user_type" id="user_type" class="user-type-select" required>
                     <option value="">Select User Type</option>
                     <option value="customer">Customer</option>
                     <option value="owner">Owner</option>
                 </select>
             </div>
             
             <!-- Username Field (same for both) -->
             <div class="col-mb-3 mt-3">
                 <label for="username_email"><i class="fa fa-user"></i> Username</label>
                 <input type="text" name="username_email" id="username_email" class="form-control" required 
                        placeholder="Enter your username">
                 <small class="text-muted">Both customers and owners login with username</small>
             </div>
             
             <div class="col mb-3 mt-3">
                 <label for="password"><i class="fa fa-lock"></i> Password</label>
                 <input type="password" name="password" id="password" class="form-control" required>
             </div>
             <div class="col mb-3 mt-3">
                 <button type="submit" class="btn btn-success bg-success w-100" style="font-weight: 600;">Login</button>
             </div>
             <div class="col mb-2 mt-4">
                 <p class="text-center" style="font-weight: 600; color: navy;">
                     <a href="register.php" style="text-decoration: none;">Create Customer Account</a>
                 </p>
                 <p class="text-center mt-2" style="font-weight: 600; color: navy;">
                     <a href="resetpassword.php" style="text-decoration: none;">Forgot Password</a>
                 </p>
             </div>
         </form>
     </div>
     
     <script>
         // Initialize and show the toast notification
         let toastElList = [].slice.call(document.querySelectorAll('.toast'))
         let toastList = toastElList.map(function (toastEl) {
             const isError = toastEl.classList.contains('bg-danger');
             return new bootstrap.Toast(toastEl, { delay: isError ? 10000 : 3000 });
         });
         toastList.forEach(toast => toast.show());
     </script>
</body>
</html>