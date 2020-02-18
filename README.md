# OSS-PHP-SDK v1

---

### 一、安装

##### [1、源码安装](#code)

##### [2、说明](#description)

### 二、快速入门

#### 1、BUCKET操作

##### [1.1、新建bucket](#put_bucket)

##### [1.2、删除bucket](#delete_bucket)

##### [1.3、获取bucket](#get_bucket)

##### [1.4、获取bucket acl](#get_bucket_acl)

##### [1.5、更新bucket acl](#put_bucket_acl)

##### [1.6、获取bucket list](#get_bucket_list)

##### [1.7、检测bucket是否存在](#bucket_exist)

#### 2、OBJECT操作

##### [2.1、根据内容上传文件](#put_object_content)

##### [2.2、根据路径上传文件](#put_object_path)

##### [2.3、删除object](#delete_object)

##### [2.4、批量删除object](#delete_objects)

##### [2.5、获取object](#get_object_meta)

##### [2.6、下载object](#get_object_info)

##### [2.7、获取object acl](#get_object_acl)

##### [2.8、更新object acl](#put_object_acl)

##### [2.9、检测object是否存在](#object_exist)

#### 3、生成访问链接

##### [3.1、生成访问链接](#sign_rtmp_url)


---
### 一、安装

<span id="code">1、源码安装</span>

- 在GitHub中选择相应版本并下载打包好的zip文件。
- 解压后的根目录中包含一个autoload.php文件，在代码中引入此文件：

```php
<?php 
require_once '/path/to/oss-sdk/autoload.php';
```

<span id="description">2、说明</span>
> ① 配置文件(config.php)配置相应的配置选项;实例化对象，可以不需要传入相应参数，如果 传入参数，以 实例化时传参为准。   
> ② 如果配置文件 没有配置 OSS_ACCESS_ID、OSS_ACCESS_KEY、OSS_ENDPOINT；实例对象的时候，需填入相应的值。如：
```php
<? php
new OssClient("<yourAccessKeyId>","<yourAccessKeySecret>","<yourEndPoint>");
```

### 二、快速入门

#### 1、BUCKET操作

<span id="put_bucket">1.1、新建bucket</span>

```php
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


```

<span id="delete_bucket">1.2、删除bucket</span>

```php
<?php
/**
 * Test demo
 *
 * Deletes bucket
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
    $re = $client->deleteBucket('<yourBucketName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}


print_r($re);


```

<span id="get_bucket">1.3、获取bucket</span>

```php
<?php
/**
 * Test demo
 *
 * Get the Meta information for the Bucket
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
    $re = $client->getBucketMeta('<yourBucketName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);

```

<span id="get_bucket_acl">1.4、获取bucket acl</span>

```php
<?php
/**
 * Test demo
 *
 * Gets the bucket ACL
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
    $re = $client->getBucketAcl('<yourBucketName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}


print_r($re);

```

<span id="put_bucket_acl">1.5、更新bucket acl</span>

```php
<?php
/**
 * Test demo
 *
 * Sets the bucket ACL
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
    $re = $client->putBucketAcl('<yourBucketName>','<yourBucketAcl>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);

```

<span id="get_bucket_list">1.6、获取bucket list</span>

```php
<?php
/**
 * Test demo
 *
 * Lists the Bucket
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
    $re = $client->listBuckets();
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);

```

<span id="bucket_exist">1.7、检测bucket是否存在</span>

```php
<?php
/**
 * Test demo
 *
 * Checks if a bucket exists
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
    $re = $client->doesBucketExist('<yourBucketName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

var_dump($re);
```

#### 2、OBJECT操作

<span id="put_object_content">2.1、根据内容上传文件</span>

```php
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
```

<span id="put_object_path">2.2、根据路径上传文件</span>

> 如果上传大文件，你需要再php.ini或代码里设置 ini_set 参数，使它足够大。

```php
<?php
/**
 * Test demo
 *
 * Uploads the $content object to OSS.
 * @param string $bucketname Your bucket name
 * @param string $object object name
 * @param string $acl The AccessKeySecret from OSS or STS
 */

// ini_set('memory_limit','2048M');

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
    $re = $client->uploadFile('<yourBucketName>', '<yourObjectName>', '<yourDownloadPath>', '<yourObjectAcl>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```

<span id="delete_object">2.3、删除object</span>

```php
<?php
/**
 * Test demo
 *
 * Deletes a object
 * @param string $bucketname Your bucket name
 * @param string $object object name
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
    $re = $client->deleteObject('<yourBucketName>', '<yourObjectName>');
    
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```

<span id="delete_objects">2.4、批量删除object</span>

```php
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
```

<span id="get_object_meta">2.5、获取object</span>

```php
<?php
/**
 * Test demo
 *
 * get object meta data
 * @param string $bucketname Your bucket name
 * @param string $object object name
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
    $re = $client->getObjectMeta('<yourBucketName>', '<yourObjectName>');

} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```

<span id="get_object_info">2.6、下载object</span>

###### 配置文件配置文件下载路径，OSS_FILE_DOWNLOAD 

```php
<?php
/**
 * Test demo
 *
 * get object data
 * @param string $bucketname Your bucket name
 * @param string $object object name
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
    $re = $client->getObject('<yourBucketName>', '<yourObjectName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```

<span id="get_object_acl">2.7、获取object acl</span>

```php
<?php
/**
 * Test demo
 *
 * get bucket's object acl permissions
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
    $re = $client->getObjectAcl('<yourBucketName>', '<yourObjectName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```

<span id="put_object_acl">2.8、更新object acl</span>

```php
<?php
/**
 * Test demo
 *
 * update bucket’s object acl permissions
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
    $re = $client->putObjectAcl('<yourBucketName>', '<yourObjectName>', '<yourObjectAcl>');

} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}


print_r($re);
```

<span id="object_exist">2.9、检测object是否存在</span>

```php
<?php
/**
 * Test demo
 *
 * Checks if the object exists
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
    $re = $client->doesObjectExist('<yourBucketName>', '<yourObjectName>');
} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

var_dump($re);
```

#### 3、生成访问链接

###### timeout 默认 60秒 

<span id="sign_rtmp_url">3.1、生成访问链接</span>

```php
<?php
/**
 * Test demo
 *
 * Generates the signed pushing streaming url.
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
    $re = $client->signRtmpUrl('<yourBucketName>', '<yourObjectName>', '<timeout>');

} catch (Exception $e) {
    print("Exception:" . $e->getMessage() . "\n");
}

print_r($re);
```