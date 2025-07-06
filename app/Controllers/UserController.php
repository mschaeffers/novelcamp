<?php

namespace App\Controllers;

use App\DataModel\FilterParameter;
use App\DataModel\FilterParameterCollection;
use App\DataModel\Session;
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

    public function Login(string $email, string $password): bool
    {
        // Logic for user login
        // This would typically involve validating input and checking the credentials against a database.
        if (empty($email) || empty($password)) {
            return false; // Validation failed
        }

        $search = new FilterParameterCollection([
            new FilterParameter('Email', $email, FilterParameter::EQUALS)
        ]
        );
        
        $user = User::GetFirstWhere($search);
        if ($user && password_verify($password . $user->Salt, $user->PasswordHash)) {
            // Set session or token for the logged-in user
            $session = new Session();
            $session->UserId = $user->UserId;
            $session->SessionToken = bin2hex(random_bytes(32)); // Generate a random session token
            $session->loginTime = new \DateTime();
            $session->Insert(); // Save session to the database

            // Set session variables
            $_SESSION['user_id'] = $user->UserId;
            $_SESSION['username'] = $user->Username;
            $_SESSION['email'] = $user->Email;
            $_SESSION['session_token'] = $session->SessionToken;
            return true; // Login successful
        }
        
        return false; // Invalid credentials
    }
}
