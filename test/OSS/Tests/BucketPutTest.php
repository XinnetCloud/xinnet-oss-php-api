<?php
/**
 * Test demo
 *
 * Creates bucket,The ACL of the bucket created by default is OssClient::OSS_ACL_TYPE_PRIVATE
 * @param string $bucketname Your bucket name
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
	$re = $client->createBucket('<yourBucketName>','<yourBucketAcl>');
} catch (Exception $e) {
	print("Exception:" . $e->getMessage() . "\n");
}
print_r($re);
