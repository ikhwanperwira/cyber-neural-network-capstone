<?php
require 'aws-config.php';
require 'validation.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;

header('Content-Type: application/json');

// Mendapatkan token dari header Authorization
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Fungsi untuk mengirim respon JSON dan keluar
function jsonResponse($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(array_merge(['detail' => $message, 'is_success' => $status == 200], $data));
    exit();
}

try {
    $expectedAud = $clientId;
    $decodeToken = validateToken($token, $expectedAud);
} catch (Exception $e) {
    jsonResponse(401, 'Token tidak valid: ' . $e->getMessage());
}

// Cek apakah file gambar telah diunggah
if (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) {
    jsonResponse(400, 'File gambar tidak ditemukan atau terjadi kesalahan upload');
}

// Unggah file gambar ke server Python untuk inferensi
$imagePath = $_FILES['image']['tmp_name'];
$inferApiUrl = 'http://54.179.160.218:8080/infer'; //kalau fargate dimatikan maka publik IP berubah

$ch = curl_init($inferApiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($imagePath)]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Tambahkan opsi debug curl
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));

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

// Unduh gambar dari S3 bucket berdasarkan MD5
$bucket = 'imagecnnfiles';
$filename = $md5 . '.jpg';

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
