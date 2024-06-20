<?php
require 'aws-config.php';

use Aws\Exception\AwsException;

$email = isset($_GET['email']) ? $_GET['email'] : (isset($_POST['email']) ? $_POST['email'] : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $confirmationCode = $_POST['code'];

    if (empty($email)) {
        echo 'Error: Email is required.';
        exit();
    }

    try {
        $result = $client->confirmSignUp([
            'ClientId' => $clientId,
            'Username' => $email, //menggunakan email sebagai username
            'ConfirmationCode' => $confirmationCode,
        ]);

        // Redirect ke halaman login setelah verifikasi berhasil
        header("Location: login.php?verified=true");
        exit();
    } catch (AwsException $e) {
        echo 'Error: ' . $e->getAwsErrorMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    .main {
      height: 100vh;
      background-color: black;
    }

    .verify-box {
      width: 500px;
      height: 300px;
      box-sizing: border-box;
      border-radius: 10px;
      background-color: blueviolet;
    }
</style>
<body>
<div class="main d-flex flex-column justify-content-center align-items-center">
    <div class="verify-box p-5 shadow">
        <form method="POST">
            <h5 class="text-center text-white">Verify</h5>
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="form-group">
            <label for="password">Verification Code : </label>
            <input type="password" name="password" class="form-control" required>
            </div><br>
            <input class="btn btn-success form-control mt-3 mb-3" type="submit" value="Verify">
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>