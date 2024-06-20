<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

// AWS credentials
$accesKeyId = 'AKIA4MTWND2JNCKJ7QQA';
$secretAccesKey = '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';
$accesKeyDBId = 'AKIA4MTWND2JNHDVRZXJ';
$secretAccesKeyDB = '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';
$region = 'ap-southeast-1';

//configure AWS SDK
$s3 = new S3Client([
    'version' => 'latest',
    'region' => $region,
    'credentials' => [
        'key' => $accesKeyId,
        'secret' => $secretAccesKey,
    ],
 ]);

$client = new CognitoIdentityProviderClient([
    'region' => 'ap-southeast-1', // Ganti dengan region AWS
    'version' => 'latest',
    'credentials' => [
        'key'    => $accesKeyId, // Ganti dengan AWS Access Key
        'secret' => $secretAccesKey, // Ganti dengan AWS Secret Key
    ],
]);

//dynamodb
$dynamoDb = new DynamoDbClient([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => $accesKeyDBId,
        'secret' => $secretAccesKeyDB,
    ]
]);
$tableName = 'history-infer'; 
$bucket = 'imagecnnfiles'; // S3 bucket name
$userPoolId = 'ap-southeast-1_2V8pNp9OI'; // Ganti dengan User Pool ID
$clientId = 'bve1gspvghs797f9t6316atdg'; // Ganti dengan App Client ID