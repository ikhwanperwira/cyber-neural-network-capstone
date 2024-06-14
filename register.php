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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    .main{
      height: 100vh;
      background-color: black;
    }

    .login-box{
      width: 500px;
      height: 500px;
      box-sizing: border-box;
      border-radius: 10px;
      background-color: blueviolet;
    }
</style>
<div>
<div class="main d-flex flex-column justify-content-center align-items-center">
    <div class="login-box p-5 shadow">
<form method="POST">
    <h5 class="text-center text-white">Register</h5>
    <div>
        <label for="email">Email: </label>
        <input type="email" name="email" class="form-control" required>
    </div><br>
    <div>
        <label for="password">Password: </label>
        <input type="password" name="password" class="form-control" required>
    </div><br>
    <button class="btn btn-success form-control mt-3 mb-3" type="submit" value="Register">Register</button>
    <a href="login.php" class="text-daftar btn btn-primary">Login</a></p>
</form>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>