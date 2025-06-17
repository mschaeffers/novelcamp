<?php

namespace App\Controllers;

use App\DataModel\User;

class UserController
{
    public function SignUp(string $username, string $email, string $password): bool
    {
        // Logic for user sign-up
        // This would typically involve validating input, hashing the password,
        // and saving the user data to a database.
        if (empty($username) || empty($email) || empty($password)) {
            return false; // Validation failed
        }
        $user = new User();
        $user->Username = $username; 
        $user->Email = $email;
        // Generate a random salt
        $salt = bin2hex(random_bytes(16));
        // Hash the password with the salt
        $user->PasswordHash = password_hash($password . $salt, PASSWORD_BCRYPT);
        $user->Salt = $salt;

        $user->Insert();
        return true; // Sign-up successful
    }
}
