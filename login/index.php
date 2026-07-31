<?php
session_start();

// Include database connection
$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . "/attapp/database/database.php"; 

$error = "";

// Check if form is submitted
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to check credentials
    $sql = "SELECT * FROM faculty_details WHERE user_name='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        // Login successful
        $_SESSION['faculty'] = $username;
       header("Location: /attapp/dashboard/dashboard.php");
 // adjust path if dashboard.php is in parent folder
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Faculty Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        input { padding: 5px; margin: 5px; }
        .error { color: red; }
    </style>
</head>
<body>

<h2>Faculty Login</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>

<form method="post">
    Username: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" name="login" value="Login">
</form>

</body>
</html>
