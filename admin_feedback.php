<?php
include('includes/db_connect.php');

$sql = "SELECT * FROM feedback ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<h2>Feedback List</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Customer ID</th>
    <th>Message</th>
    <th>Date</th>
</tr>

<?php
while($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>".$row['feedback_id']."</td>
        <td>".$row['customer_id']."</td>
        <td>".$row['message']."</td>
        <td>".$row['created_at']."</td>
    </tr>";
}
?>

</table>