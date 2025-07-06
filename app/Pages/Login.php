<?php

use App\Controllers\UserController;

//If Post, call UserController to handle sign up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $userController = new UserController();
    $userController->Login($_POST['email'], $_POST['password']);
    header('Location: ../index.php');
    exit();
}
?>
<html>

<head>
    <title>Login Page</title>
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>

<body>
    <p><a href="../index.php">Home</a></p>
    <h1>Login</h1>
   <form action="Login.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="login">
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" minlength="8" required>
    </form>

</html>