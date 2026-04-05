<?php
// Script to verify and fix owner1 password hash

include 'includes/db_connect.php';

if ($conn && $conn instanceof mysqli) {
    $username = 'owner1';
    $desired_password = 'owner123';
    
    echo "<h2>Fixing Owner Account...</h2>";
    
    // Get current owner from database
    $check_sql = "SELECT owner_id, owner_username, owner_password, owner_full_name FROM owners WHERE owner_username = ?";
    if ($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $owner = $result->fetch_assoc();
            echo "<p>Found owner: " . $owner['owner_username'] . "</p>";
            
            $db_password = $owner['owner_password'];
            echo "<p>Current password in DB: " . (empty($db_password) ? '(empty)' : $db_password) . "</p>";
            
            // Hash the desired password
            $hashed_password = password_hash($desired_password, PASSWORD_DEFAULT);
            
            // Update to hashed password
            $update_sql = "UPDATE owners SET owner_password = ? WHERE owner_username = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("ss", $hashed_password, $username);
                if ($update_stmt->execute()) {
                    echo "<p style='color: green;'>✓ Password updated to HASHED version successfully!</p>";
                    echo "<p>Username: owner1</p>";
                    echo "<p>Password: owner123 (now stored as hash)</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error updating password: " . $update_stmt->error . "</p>";
                }
                $update_stmt->close();
            }
        } else {
            echo "<p style='color: red;'>Owner 'owner1' not found in database.</p>";
            
            // Create owner with hashed password
            $hashed_password = password_hash($desired_password, PASSWORD_DEFAULT);
            $full_name = 'Owner';
            
            $insert_sql = "INSERT INTO owners (owner_username, owner_password, owner_full_name) VALUES (?, ?, ?)";
            if ($insert_stmt = $conn->prepare($insert_sql)) {
                $insert_stmt->bind_param("sss", $username, $hashed_password, $full_name);
                if ($insert_stmt->execute()) {
                    echo "<p style='color: green;'>✓ Owner 'owner1' created successfully!</p>";
                    echo "<p>Username: owner1</p>";
                    echo "<p>Password: owner123 (hashed)</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error creating owner: " . $insert_stmt->error . "</p>";
                }
                $insert_stmt->close();
            }
        }
        $check_stmt->close();
    }
    
    $conn->close();
} else {
    echo "<p style='color: red;'>Database connection failed. Please check db_connect.php</p>";
}
?>