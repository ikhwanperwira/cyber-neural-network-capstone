<?php
require 'aws-config.php';

use Aws\Exception\AwsException;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];


    try {
        $result = $client->signUp([
            'ClientId' => $clientId,
            'Username' => $email,// Menggunakan email sebagai username
            'Password' => $password,
            'UserAttributes' => [
                [
                    'Name' => 'email',
                    'Value' => $email,
                ],
            ],
        ]);

        // Redirect ke halaman verifikasi
        header("Location: verify.php?email=" . urlencode($email));
        exit();
    } catch (AwsException $e) {
        echo 'Gagal register: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
        body {
            background-image: linear-gradient(to right, #6a11cb, #2575fc);
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="card p-8 w-full max-w-md">
        <!-- Login Page -->
        <div id="register-page">
            <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Register</h2>
            
            <form action="" method="POST">
            <div class="form-group">
            <label for="email">Email: </label>
            <input type="email" name="email" class="form-control" required>
            </div><br>
            <div class="form-group">
            <label for="password">Password: </label>
            <input type="password" name="password" class="form-control" required>
            </div><br>
            <button type="submit" class="btn btn-success form-control mb-3">Register</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">Already have account? <a href="login.php" class="text-indigo-600 hover:underline" >login</a></p>
        </div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>