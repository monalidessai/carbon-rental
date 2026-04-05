<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
</head>
<body>

<h2>Give Your Feedback</h2>

<form action="submit_feedback.php" method="POST">

    <!-- TEMP: customer_id (important for your table) -->
    <input type="hidden" name="customer_id" value="1">

    <label>Message:</label><br>
    <textarea name="message" rows="5" cols="30" required></textarea><br><br>

    <button type="submit">Submit</button>

</form>

</body>
</html>