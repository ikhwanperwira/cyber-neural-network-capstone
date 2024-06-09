<?php
require 'aws-config.php';
require 'vendor/autoload.php';

use Aws\Exception\AwsException;

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
            $result = $client->initiateAuth([
                'ClientId' => $clientId,
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $email,
                    'PASSWORD' => $password,
                ],
            ]);

            $authenticationResult = $result['AuthenticationResult'];

            // Simpan AccessToken dalam sesi
            $_SESSION['id_token'] = $result['AuthenticationResult']['id_token'];
            $_SESSION['email'] = $email;
        
            echo '<pre>';
            var_dump($authenticationResult);
            echo '</pre>';
        //header('Location: index.php');
        //exit();
        } catch (AwsException $e) {
            echo 'Login gagal: ' . $e->getAwsErrorMessage();
        }
    }

$verified = isset($_GET['verified']) && $_GET['verified'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
<body>
<div class="main d-flex flex-column justify-content-center align-items-center">
    <div class="login-box p-5 shadow">
    <h5 class="text-center text-white">Login</h5>
    <?php if ($verified): ?>
                        <div class="alert alert-success" role="alert">
                            Verification successful! you can login now.
                        </div>
    <?php endif; ?>
    <form action="" method="POST">
        <div class="form-group">
            <label for="email">Email: </label>
            <input type="email" name="email" class="form-control" required>
        </div><br>
    <div class="form-group">
        <label for="password">Password: </label>
        <input type="password" name="password" class="form-control" required>
    </div><br>
<button type="submit" class="btn btn-success form-control mb-3">Login</button>
</form>

<p class="text-wthite">Belum punya akun? <a href="register.php" class="text-daftar btn btn-primary">Daftar sekarang</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html> 