<?php
/**
 * Test demo
 *
 * deltete bucket
 * @param string $bucketname Your bucket name
 */

if (is_file(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
if (is_file(__DIR__ . '/../../../pppcloud-oss-php-sdk/autoload.php')) {
    require_once __DIR__ . '/../../../pppcloud-oss-php-sdk/autoload.php';
}

use OSS\OssClient;

// $client = new OssClient("<yourAccessKeyId>","<yourAccessKeySecret>","<yourEndPoint>");
$client = new OssClient();


try {
	$re = $client->deleteObjects('<yourBucketName>',array('<yourObjectName1>','<yourObjectName2>'));
} catch (Exception $e) {
	print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);