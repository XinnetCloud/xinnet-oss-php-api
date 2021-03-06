<?php
/**
 * Test demo
 *
 * Uploads the $content object to OSS.
 * @param string $bucketname Your bucket name
 * @param string $object object name
 * @param string $acl The AccessKeySecret from OSS or STS
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
	$re = $client->putObject('<yourBucketName>', '<yourObjectName>','<yourContent>', '<yourObjectAcl>');
} catch (Exception $e) {
	print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);