<?php

use App\Controllers\UserController;

//If Post, call UserController to handle sign up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'sign_up') {
    $userController = new UserController();
    $userController->SignUp($_POST['username'], $_POST['email'], $_POST['password']);
    header('Location: ../index.php');
    exit();
}

?>

<html>

<head>
    <title>Sign Up Page</title>
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>

<body>
    <p><a href="../index.php">Home</a></p>
    <h1>Sign Up</h1>
    <form action="SignUp.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="sign_up">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" minlength="8" required>
    </form>
</body>

</html>