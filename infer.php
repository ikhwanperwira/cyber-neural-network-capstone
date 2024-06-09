<?php
require 'aws-config.php';
require 'vendor/autoload.php';

use Aws\Exception\AwsException;

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $token = $_POST['token'];

        // TODO: validate token
        
        // Retrieve image from user request
        $image = $_FILES['image']['tmp_name'];

        // Move image to /tmp directory with name infer_input.dat
        move_uploaded_file($image, 'input.dat');
        // Keluarannya ada di infer_output.jpg

        try {
            // Call python binding with exec to infer the image
            exec('python3 infer.py', $output, $return);
        } catch (Exception $e) {
            echo 'Infer gagal: ' . $e;
        }

        // TODO: upload to s3
    }
?>
