<?php
require 'aws-config.php';
require 'validation.php';

session_start();

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['idToken']) || !isset($_SESSION['refresh_token'])) {
    header('Location: login.php');
    exit();
}

refreshTokenIfNeeded($client, $clientId);

// Validate token
$expectedAud = $clientId;
try {
    $decodedToken = validateToken($_SESSION['idToken'], $expectedAud);
} catch (Exception $e) {
    echo 'Token validation failed: ' . $e->getMessage();
}

 // Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['files']['name'][0])) {
    $uploadedFiles = $_FILES['files'];
    $fileCount = count($uploadedFiles['name']);

    try {
        for ($i = 0; $i < $fileCount; $i++) {
            $tmpFilePath = $uploadedFiles['tmp_name'][$i];
            $fileName = $uploadedFiles['name'][$i];
            $s3->putObject([
                'Bucket' => $bucket,
                'Key' => $fileName,
                'Body' => fopen($tmpFilePath, 'rb'),
            ]);
        }

        echo 'Files uploaded successfully.';
        header('Location: index.php');
    } catch (AwsException $e) {
        echo 'Error uploading files: ' . $e->getMessage();
    }
}

try {
    $objects = $s3->listObjects([
        'Bucket' => $bucket,
    ]);

    $files = isset($objects['Contents']) ? $objects['Contents'] : [];
} catch (AwsException $e) {
    echo 'Gagal mengambil daftar file: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<div>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNN Inferred</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script defer src="script.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
 
<body>
<h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['email']); ?>!</h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <input type="file" name="files[]" class="form-control-file mt-3" multiple required>
        </div>
        <input type="submit" value="Upload" class="btn btn-primary mt-2">Upload Image</input>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Image</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <h2 class="mt-3"> Image pada s3 Bucket</h2>
        <?php if (!empty($files)): ?>
        <?php foreach ($files as $file) : ?>
        <?php $fileUrl = 'https://' . $bucket . '.s3.amazonaws.com/' . $file['Key']; ?>
        <tr>
            <td>
                <img src="<?php echo $fileUrl; ?>" alt="<?php echo $file['Key']; ?>" style="max-width: 200px;">
                <p><?php echo $fileUrl; ?></p> <!-- Debug: Display the URL -->
            </td>
            <td>
                <a href="?download=<?php echo $file['Key']; ?>"class="btn btn-primary">Download</a>
                <a href="?delete=<?php echo $file['Key']; ?>"class="btn btn-danger">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <p>Tidak ada gambar pada S3 bucket.</p>
    <?php endif; ?>
    </tbody>
    </table>
</body>
    
    <?php

//handle file deletion
if (isset($_GET['delete'])) {
    $key = $_GET['delete'];

    try {
        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);

        echo 'File deleted successfully.' ;
        header('Location: index.php');
    } catch (S3Exception $e) {
        echo 'Error deleting file: ' . $e->getMessage();
    }
}

//handle file download
if (isset($_GET['download'])) {
    $key = $_GET['download'];

    try {
        $result = $s3->getObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);

        header('Content-Type: ' . $result['ContentType']);
        header('Content-Dispotion: attachment; filename="' . $key . '"');
        header('Content-Length: ' . $result['ContentLength']);

        readfile($result['Body']);
        exit;
} catch (S3Exception $e) {
    echo 'Error downloading file: ' . $e->getMessage();
}
}
    ?>

<a href="logout.php" class="btn btn-danger">Logout</a></p>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</html>