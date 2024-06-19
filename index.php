<?php
require 'aws-config.php';
require 'validation.php';

session_start();

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
date_default_timezone_set('Asia/Jakarta');

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

function saveDataToDynamoDb($dynamoDb, $tableName, $email, $tanggal, $url_s3_bucket) {
    try {
        $dynamoDb->putItem([
            'TableName' => $tableName,
            'Item' => [
                'email' => ['S' => $email],
                'tanggal' => ['S' => $tanggal],
                'url_s3_bucket' => ['S' => $url_s3_bucket],
            ],
        ]);
        return true;
    } catch (DynamoDbException $e) {
        error_log("Gagal menyimpan data ke DynamoDB: " . $e->getMessage());
        return false;
    }
}

// Unggah file dan proses inferensi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    // Cek apakah file gambar telah diunggah
    if (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) {
        jsonResponse(400, 'File gambar tidak ditemukan atau terjadi kesalahan upload');
    }

    // Unggah file gambar ke server Python untuk inferensi
    $imagePath = $_FILES['image']['tmp_name'];
    $inferApiUrl = 'http://13.214.136.181:8080/infer'; // Ganti dengan URL API inferensi Anda

    $ch = curl_init($inferApiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($imagePath)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        $curlError = curl_error($ch);
        curl_close($ch);
        jsonResponse(500, 'Gagal memanggil API infer', ['error' => $curlError]);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        jsonResponse($httpCode, 'Gagal memanggil API infer', ['response' => $response]);
    }

    $responseData = json_decode($response, true);
    if (!isset($responseData['md5'])) {
        jsonResponse(500, 'Respon dari API infer tidak valid', ['response' => $response]);
    }

    $md5 = $responseData['md5'];

    // Informasi gambar di S3
    $bucket = 'imagecnnfiles';
    $filename = $md5 . '.jpg';
    $email = $_SESSION['email'];
    $tanggal = date('Y-m-d H:i:s');
    $url_s3_bucket = 'https://' . $bucket . '.s3.amazonaws.com/' . $filename;
    
    error_log("Menyimpan ke DynamoDB: Email = $email, Tanggal = $tanggal, URL S3 = $url_s3_bucket");
    
    $saveResult = saveDataToDynamoDb($dynamoDb, $tableName, $email, $tanggal, $url_s3_bucket);
    if (!$saveResult) {
        jsonResponse(500, 'Gagal menyimpan data ke DynamoDB');
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

        $result = $s3->getObject([
            'Bucket' => $bucket,
            'Key'    => $filename
        ]);

        // Encode gambar ke base64
        $imageBase64 = base64_encode($result['Body']);

        // Kembalikan respon JSON dengan gambar base64
        jsonResponse(200, 'Infer berhasil', ['md5' => $md5, 'image_base64' => $imageBase64]);

    } catch (S3Exception $e) {
        jsonResponse(500, 'Gagal mengunduh gambar dari S3', ['error' => $e->getMessage()]);
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $key = $_GET['delete'];

    try {
        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);

        echo 'File deleted successfully.';
        header('Location: index.php');
    } catch (S3Exception $e) {
        echo 'Error deleting file: ' . $e->getMessage();
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $key = $_GET['download'];

    try {
        $result = $s3->getObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        header('Content-Type: ' . $result['ContentType']);
        header('Content-Disposition: attachment; filename="' . basename($key) . '"');
        echo $result['Body'];
        exit();
    } catch (S3Exception $e) {
        echo 'Error saat mengunduh file dari S3: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-purple-500 to-blue-500 flex justify-center items-center min-h-screen relative">
    <div class="absolute top-4 right-4">
        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
    </div>
    <div class="absolute top-4 left-4 text-white text-2xl font-bold">
        Selamat datang, <?php echo htmlspecialchars($_SESSION['email']); ?>!
    </div>
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h1 class="text-2xl font-bold text-center mb-4">Upload Image</h1>

        <form id="upload-form" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-gray-700">Select Image</label>
                <input type="file" name="image" class="mt-1 p-2 border w-full" required>
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">Upload</button>
        </form>

        <div class="mt-4 text-center">
            <a href="history.php" class="text-blue-500">View Upload History</a>
        </div>

        <div id="result" class="mt-4"></div>
    </div>

    <script>
        document.getElementById('upload-form').addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData();
            const imageFile = document.getElementById('image').files[0];
            formData.append('image', imageFile);

            const token = '<?php echo $_SESSION['idToken']; ?>';

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.is_success) {
                    const imgElement = document.createElement('img');
                    imgElement.src = 'data:image/jpeg;base64,' + result.image_base64;
                    imgElement.alt = 'Inferred Image';
                    imgElement.style.maxWidth = '100%';

                    document.getElementById('result').innerHTML = `
                        <h2>Inference Result</h2>
                        <p>MD5: ${result.md5}</p>
                    `;
                    document.getElementById('result').appendChild(imgElement);
                } else {
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            ${result.detail}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        Error: ${error.message}
                    </div>
                `;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>