<?php
require 'aws-config.php';
require 'validation.php';

session_start();

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['idToken']) || !isset($_SESSION['refresh_token'])) {
    header('Location: login.php');
    exit();
}

// Refresh token jika diperlukan
refreshTokenIfNeeded($client, $clientId);

// Validasi token
$expectedAud = $clientId;
try {
    $decodedToken = validateToken($_SESSION['idToken'], $expectedAud);
} catch (Exception $e) {
    echo 'Token validation failed: ' . $e->getMessage();
    exit();
}

// Fungsi untuk mengirim respon JSON dan keluar
function jsonResponse($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(array_merge(['detail' => $message, 'is_success' => $status == 200], $data));
    exit();
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $key = $_GET['delete'];

    try {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accesKeyId,
                'secret' => $secretAccesKey,
            ],
        ]);

        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);

        echo 'File deleted successfully.';
        header('Location: history.php');
    } catch (S3Exception $e) {
        echo 'Error deleting file: ' . $e->getMessage();
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $key = $_GET['download'];

    try {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accesKeyId,
                'secret' => $secretAccesKey,
            ],
        ]);

        $result = $s3->getObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($key) . '"');
        echo $result['Body'];
        exit();
    } catch (S3Exception $e) {
        echo 'Error downloading file: ' . $e->getMessage();
    }
}

try {
    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region,
        'credentials' => [
            'key' => $accesKeyId,
            'secret' => $secretAccesKey,
        ],
    ]);

    $result = $s3->listObjectsV2([
        'Bucket' => $bucket
    ]);

    $files = $result['Contents'];
} catch (S3Exception $e) {
    echo 'Error retrieving files: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload History</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-purple-500 to-blue-500 flex justify-center items-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-2xl">
        <h1 class="text-2xl font-bold text-center mb-4">Upload History</h1>
        <table class="table-auto w-full border-collapse border border-gray-200">
            <thead>
                <tr>
                    <th class="border border-gray-300 p-2">File Name</th>
                    <th class="border border-gray-300 p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="border border-gray-300 p-2"><?php echo $file['Key']; ?></td>
                        <td class="border border-gray-300 p-2 text-center">
                            <a href="history.php?download=<?php echo $file['Key']; ?>" class="text-blue-500">Download</a> |
                            <a href="history.php?delete=<?php echo $file['Key']; ?>" class="text-red-500">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-blue-500">Back to Upload</a>
        </div>
    </div>
</body>
</html>