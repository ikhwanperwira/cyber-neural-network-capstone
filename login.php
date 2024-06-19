<?php
require 'aws-config.php';

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
            $_SESSION['access_token'] = $result['AuthenticationResult']['AccessToken'];
            $_SESSION['idToken'] = $result['AuthenticationResult']['IdToken'];
            $_SESSION['email'] = $email;
            $_SESSION['refresh_token'] = $result['AuthenticationResult']['RefreshToken'];
            $_SESSION['access_token_expiration'] = time() + $authenticationResult['ExpiresIn'];

        //echo '<pre>';
        //var_dump($authenticationResult);
        //echo '</pre>';
        header('Location: index.php');
        exit();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
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
        <div id="login-page">
            <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Login</h2>
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
            <p class="mt-4 text-center text-sm text-gray-600">Don't have an account? <a href="register.php" class="text-indigo-600 hover:underline" >Register</a></p>
        </div>
    
    



<?php if (isset($authenticationResult)): ?>
        <div class="alert alert-info" role="alert">
            <strong>Access Token:</strong> <?php echo htmlspecialchars($authenticationResult['AccessToken']); ?><br>
            <strong>ID Token:</strong> <?php echo htmlspecialchars($authenticationResult['IdToken']); ?><br>
            <strong>Refresh Token:</strong> <?php echo htmlspecialchars($authenticationResult['RefreshToken']); ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>