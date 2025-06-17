<?php

require_once dirname(__FILE__) . "\..\autoload.php";

use App\Controllers\UserController;

$userController = new UserController();
$username = "Spacemonkey";
$email = "mschaeffers@outlook.com";
$password = "password123";
    
$userController->SignUp($username, $email, $password);