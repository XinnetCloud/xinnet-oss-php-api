<?php
namespace OSS;

require_once __DIR__ . '/../../Config.php';

use Config;

use OSS\Core\MimeTypes;
use OSS\Core\OssUtil;
use OSS\Http\RequestCore;
use OSS\Http\ResponseCore;
use OSS\Result\ListBucketsResult;
use OSS\Result\PutSetDeleteResult;
use OSS\Result\ExistResult;
use OSS\Result\HeaderResult;
use OSS\Result\AclResult;
use OSS\Result\BodyResult;
use OSS\Result\CallbackResult;


class OssClient
{
    /**
     * Constructor
     *
     * There're a few different ways to create an OssClient object:
     * 1. Most common one from access Id, access Key and the endpoint: $ossClient = new OssClient($id, $key, $endpoint)
     * 2. If the endpoint is the CName (such as www.testoss.com, make sure it's CName binded in the OSS console), 
     *    uses $ossClient = new OssClient($id, $key, $endpoint, true)
     * 3. If using Alicloud's security token service (STS), then the AccessKeyId, AccessKeySecret and STS token are all got from STS.
     * Use this: $ossClient = new OssClient($id, $key, $endpoint, false, $token)
     * 4. If the endpoint is in IP format, you could use this: $ossClient = new OssClient($id, $key, “1.2.3.4:8900”)
     *
     * @param string $accessKeyId The AccessKeyId from OSS or STS
     * @param string $accessKeySecret The AccessKeySecret from OSS or STS
     * @param string $endpoint The domain name of the datacenter,For example: oss-cn-hangzhou.aliyuncs.com
     * @param boolean $isCName If this is the CName and binded in the bucket.
     * @param string $securityToken from STS.
     * @param string $requestProxy
     * @throws OssException
     */
    public function __construct($accessKeyId = NULL, $accessKeySecret = NULL, $endpoint = NULL, $isCName = false, $securityToken = NULL, $requestProxy = NULL)
    {
        $accessKeyId = trim($accessKeyId);
        $accessKeySecret = trim($accessKeySecret);
        $endpoint = trim(trim($endpoint), "/");

        if (empty($accessKeyId)) {
            $accessKeyId = Config::OSS_ACCESS_ID;
        }
        if (empty($accessKeySecret)) {
            $accessKeySecret = Config::OSS_ACCESS_KEY;
        }
        if (empty($endpoint)) {
            $endpoint = Config::OSS_ENDPOINT;
        }

        // $this->hostname = $this->checkEndpoint($endpoint, $isCName);
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        // $this->securityToken = $securityToken;
        // $this->requestProxy = $requestProxy;
        self::checkEnv();
    }


    /**
     * Lists the Bucket [GetService]. Not applicable if the endpoint is CName (because CName must be binded to a specific bucket).
     *
     * @param array $options
     * @throws OssException
     * @return BucketListInfo
     */
    public function listBuckets($options = NULL)
    {
        if ($this->hostType === self::OSS_HOST_TYPE_CNAME) {
            throw new OssException("operation is not permitted with CName host");
        }
        $this->precheckOptions($options);
        $options[self::OSS_BUCKET] = '';
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $response = $this->auth($options);
        $result = new ListBucketsResult($response);
        return $result->getData();
    }


    /**
     * Creates bucket,The ACL of the bucket created by default is OssClient::OSS_ACL_TYPE_PRIVATE
     *
     * @param string $bucket
     * @param string $acl
     * @param array $options
     * @param string $storageType
     * @return null
     */
    public function createBucket($bucket, $acl = self::OSS_ACL_TYPE_PRIVATE, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $options[self::OSS_ACL] = $acl;

        // $options[self::OSS_HEADERS] = array(self::OSS_ACL => $acl);
        if (isset($options[self::OSS_STORAGE])) {
            $this->precheckStorage($options[self::OSS_STORAGE]);
            $options[self::OSS_CONTENT] = OssUtil::createBucketXmlBody($options[self::OSS_STORAGE]);
            unset($options[self::OSS_STORAGE]);
        }
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();

    }


    /**
     * Deletes bucket
     * The deletion will not succeed if the bucket is not empty (either has objects or parts)
     * To delete a bucket, all its objects and parts must be deleted first.
     *
     * @param string $bucket
     * @param array $options
     * @return null
     */
    public function deleteBucket($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }


    /**
     * Checks if a bucket exists
     *
     * @param string $bucket
     * @return bool
     * @throws OssException
     */
    public function doesBucketExist($bucket)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }


    /**
     * Get the Meta information for the Bucket
     *
     * @param string $bucket
     * @param array $options  Refer to the SDK documentation
     * @return array
     */
    public function getBucketMeta($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $response = $this->auth($options);
        $result = new HeaderResult($response);
        return $result->getData();
    }


    /**
     * Gets the bucket ACL
     *
     * @param string $bucket
     * @param array $options
     * @throws OssException
     * @return string
     */
    public function getBucketAcl($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $options[self::OSS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new AclResult($response);
        return $result->getData();
    }

    /**
     * Sets the bucket ACL
     *
     * @param string $bucket bucket name
     * @param string $acl access permissions, valid values are ['private', 'public-read', 'public-read-write']
     * @param array $options by default is empty
     * @throws OssException
     * @return null
     */
    public function putBucketAcl($bucket, $acl, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET]       = $bucket;
        $options[self::OSS_METHOD]       = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT]       = '/';
        // $options[self::OSS_HEADERS]      = array(self::OSS_ACL => $acl);
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $options[self::OSS_SUB_RESOURCE] = 'acl';
        $options[self::OSS_ACL] = $acl;

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }


    /**
     * Gets object ACL
     *
     * @param string $bucket
     * @param string $object
     * @throws OssException
     * @return string
     */
    public function getObjectAcl($bucket, $object)
    {
        $options = array();
        $this->precheckCommon($bucket, $object, $options, true);
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_SUB_RESOURCE] = 'acl';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $options[self::OSS_ACL] = $acl;

        $response = $this->auth($options);
        $result = new AclResult($response);
        return $result->getData();
    }

    /**
     * Sets the object ACL
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $acl access permissions, valid values are ['default', 'private', 'public-read', 'public-read-write']
     * @throws OssException
     * @return null
     */
    public function putObjectAcl($bucket, $object, $acl)
    {
        $this->precheckCommon($bucket, $object, $options, true);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_HEADERS] = array(self::OSS_OBJECT_ACL => $acl);
        $options[self::OSS_SUB_RESOURCE] = 'acl';
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $options[self::OSS_ACL] = $acl;

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }


    /**
     * Gets Object metadata
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $options Checks out the SDK document for the detail
     * @return array
     */
    public function getObjectMeta($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_HEAD;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new HeaderResult($response);
        return $result->getData();


        $url = $this->endpoint . "/v1/storage/oss/".$bucket."/".$object;

        $request = new RequestCore($url,null,null,$headerArr);
        $request->set_method(self::OSS_HTTP_HEAD);
        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw(new OssException('RequestCoreException: ' . $e->getMessage()));
        }
        return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
    }


    /**
     * Gets Object content
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options It must contain ALIOSS::OSS_FILE_DOWNLOAD.
     * @return string
     */
    public function getObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new BodyResult($response);
        return $result->getData();
    }


    /**
     * Uploads the $content object to OSS.
     *
     * @param string $bucket bucket name
     * @param string $object objcet name
     * @param string $content The content object
     * @param array $options
     * @return null
     */
    public function putObject($bucket, $object, $content,$acl, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);

        $options[self::OSS_CONTENT] = $content;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_ACL]    = $acl;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;


        if (!isset($options[self::OSS_LENGTH])) {
            $options[self::OSS_CONTENT_LENGTH] = strlen($options[self::OSS_CONTENT]);
        } else {
            $options[self::OSS_CONTENT_LENGTH] = $options[self::OSS_LENGTH];
        }

        $is_check_md5 = $this->isCheckMD5($options);
 
        if ($is_check_md5) {
            $content_md5 = base64_encode(md5($content, true));
            $options[self::OSS_CONTENT_MD5] = $content_md5;
        }
        
        if (!isset($options[self::OSS_CONTENT_TYPE])) {
            $options[self::OSS_CONTENT_TYPE] = $this->getMimeType($object);
        }

        $response = $this->auth($options);
        
        if (isset($options[self::OSS_CALLBACK]) && !empty($options[self::OSS_CALLBACK])) {
            $result = new CallbackResult($response);
        } else {
            $result = new PutSetDeleteResult($response);
        }
            
        return $result->getData();


        // $headerArr = $this->auth($options);
        // $url = $this->endpoint . "/v1/storage/oss/".$bucket."/".$object;

        // $request = new RequestCore($url,null,null,$headerArr);
        // $request->set_method(self::OSS_HTTP_PUT);
        // $request->set_body($content);

        // try {
        //     $request->send_request();
        // } catch (RequestCore_Exception $e) {
        //     throw(new OssException('RequestCoreException: ' . $e->getMessage()));
        // }

        // // "$request->get_response_body()" is body data.
        // return new ResponseCore($request->get_response_header(), "", $request->get_response_code());
    }


    /**
     * Deletes a object
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options
     * @return null
     */
    public function deleteObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }


    /**
     * Deletes multiple objects in a bucket
     *
     * @param string $bucket bucket name
     * @param array $objects object list
     * @param array $options
     * @return ResponseCore
     * @throws null
     */
    public function deleteObjects($bucket, $objects, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        if (!is_array($objects) || !$objects) {
            throw new OssException('objects must be array');
        }
        
        $re_array = array('failed' => 0,'failed_list' => array(),'succeed'=>0,'succeed_list'=>array());
        foreach ($objects as $object) {
            $re_del_arr = $this->deleteObject($bucket,$object);
           
            if ($re_del_arr['info']['http_code'] == 204) {
                $re_array['succeed'] += 1;
                array_push($re_array['succeed_list'], $object);
            } else {
                $re_array['failed'] += 1;
                array_push($re_array['failed_list'], $object);
            }
        }
        return $re_array;
    }


    /**
     * Checks if the object exists
     * It's implemented by getObjectMeta().
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options
     * @return bool True:object exists; False:object does not exist
     */
    public function doesObjectExist($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;

        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * Uploads a local file
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $file local file path
     * @param array $options
     * @return null
     * @throws OssException
     */
    public function uploadFile($bucket, $object, $file, $acl, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        OssUtil::throwOssExceptionWithMessageIfEmpty($file, "file path is invalid");
        $file = OssUtil::encodePath($file);
        if (!file_exists($file)) {
            throw new OssException($file . " file does not exist");
        }
        $options[self::OSS_FILE_UPLOAD] = $file;
        $file_size = filesize($options[self::OSS_FILE_UPLOAD]);
        $is_check_md5 = $this->isCheckMD5($options);
        if ($is_check_md5) {
            $content_md5 = base64_encode(md5_file($options[self::OSS_FILE_UPLOAD], true));
            $options[self::OSS_CONTENT_MD5] = $content_md5;
        }
        if (!isset($options[self::OSS_CONTENT_TYPE])) {
            $options[self::OSS_CONTENT_TYPE] = $this->getMimeType($object, $file);
        }
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_CONTENT_LENGTH] = $file_size;


        $fp = fopen($file,"r");
        $str_data = fread($fp,filesize($file));//指定读取大小，这里把整个文件内容读取出来
        // echo $str_data = str_replace("\r\n","<br />",$str_data);
        fclose($fp);

        return $this->putObject($bucket,$object,$str_data,$acl);

        
    }


    /**
     * Generates the signed pushing streaming url
     *
     * @param string $bucket bucket name
     * @param string channelName $channelName
     * @param int timeout timeout value in seconds
     * @param array $options
     * @throws OssException
     * @return The signed pushing streaming url
     */
    public function signRtmpUrl($bucket, $channelName, $timeout = 60, $options = NULL)
    {
        $this->precheckCommon($bucket, $channelName, $options, false);
        $expires = time() + $timeout;
        $proto = 'http://172.16.100.54:8060';
        $hostname = $this->generateHostname($bucket);
        $cano_params = '';
        $query_items = array();
        $params = isset($options['params']) ? $options['params'] : array();
        uksort($params, 'strnatcasecmp');
        foreach ($params as $key => $value) {
            $cano_params = $cano_params . $key . ':' . $value . "\n";
            $query_items[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_FUNCTIONNAME] = __FUNCTION__;
        $signature = $this->auth($options);
        // echo urlencode($signature);
        // echo $signature;
        // $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret, true));

        $query_items[] = 'OSSAccessKeyId=' . rawurlencode($this->accessKeyId);
        $query_items[] = 'Expires=' . rawurlencode($expires);
        $query_items[] = 'Signature=' . rawurlencode($signature);

        return $proto . '/' . $bucket . '/' . $channelName . '?' . implode('&', $query_items);
    }


    /**
     * Gets the host name for the current request.
     * It could be either a third level domain (prefixed by bucket name) or second level domain if it's CName or IP
     *
     * @param $bucket
     * @return string The host name without the protocol scheem (e.g. https://)
     */
    private function generateHostname($bucket)
    {
        if ($this->hostType === self::OSS_HOST_TYPE_IP) {
            $hostname = $this->hostname;
        } elseif ($this->hostType === self::OSS_HOST_TYPE_CNAME) {
            $hostname = $this->hostname;
        } else {
            // Private domain or public domain
            $hostname = ($bucket == '') ? $this->hostname : ($bucket . '.') . $this->hostname;
        }
        return $hostname;
    }


    /**
     * Gets mimetype
     *
     * @param string $object
     * @return string
     */
    private function getMimeType($object, $file = null)
    {
        if (!is_null($file)) {
            $type = MimeTypes::getMimetype($file);
            if (!is_null($type)) {
                return $type;
            }
        }

        $type = MimeTypes::getMimetype($object);
        if (!is_null($type)) {
            return $type;
        }

        return self::DEFAULT_CONTENT_TYPE;
    }


    /**
     * Checks md5
     *
     * @param array $options
     * @return bool|null
     */
    private function isCheckMD5($options)
    {
        return $this->getValue($options, self::OSS_CHECK_MD5, false, true, true);
    }


    /**
     * Gets value of the specified key from the options 
     *
     * @param array $options
     * @param string $key
     * @param string $default
     * @param bool $isCheckEmpty
     * @param bool $isCheckBool
     * @return bool|null
     */
    private function getValue($options, $key, $default = NULL, $isCheckEmpty = false, $isCheckBool = false)
    {
        $value = $default;
        if (isset($options[$key])) {
            if ($isCheckEmpty) {
                if (!empty($options[$key])) {
                    $value = $options[$key];
                }
            } else {
                $value = $options[$key];
            }
            unset($options[$key]);
        }

        if ($isCheckBool) {
            if ($value !== true && $value !== false) {
                $value = false;
            }
        }
        return $value;
    }


    /**
     * Check if all dependent extensions are installed correctly.
     * For now only "curl" is needed.
     * @throws OssException
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            //Test curl extension
            $enabled_extension = array("curl");
            $extensions = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (!in_array($item, $extensions)) {
                        throw new OssException("Extension {" . $item . "} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new OssException("function get_loaded_extensions not found.");
            }
        } else {
            throw new OssException('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }


    /**
     * Validates and executes the request according to OSS API protocol.
     *
     * @param array $options
     * @return ResponseCore
     * @throws OssException
     * @throws RequestCore_Exception
     */
    private function auth($options)
    {

        $gmtDate=date('D, d M Y H:i:s \G\M\T',time());

        if ($options[self::OSS_FUNCTIONNAME] == 'listBuckets') {
            $string_to_sign=$options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH;

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . '/v1/storage/oss';
            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }

            $response = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

            return $response;


        } elseif ( in_array($options[self::OSS_FUNCTIONNAME], array('doesBucketExist'))) {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH;

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        } elseif ($options[self::OSS_FUNCTIONNAME] == 'getBucketAcl') {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . "?" . $options[self::OSS_SUB_RESOURCE];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET]."/?acl";

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        } elseif ($options[self::OSS_FUNCTIONNAME] == 'putBucketAcl') {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_AMZ_ACL .':'. $options[self::OSS_ACL] . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH. "?" . $options[self::OSS_SUB_RESOURCE];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
                self::OSS_ACL           => $options[self::OSS_ACL],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET]."/?acl";

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ($options[self::OSS_FUNCTIONNAME] == 'createBucket') {
            $string_to_sign=$options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_AMZ_ACL .':'. $options[self::OSS_ACL] . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH;

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
                self::OSS_ACL           => $options[self::OSS_ACL],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        } elseif ($options[self::OSS_FUNCTIONNAME] == 'deleteBucket') {
            $string_to_sign=$options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH;

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET];
            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());



        } elseif ($options[self::OSS_FUNCTIONNAME] == 'getBucketMeta') {
            $string_to_sign=$options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH;

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/".$options[self::OSS_BUCKET];
            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ($options[self::OSS_FUNCTIONNAME] == 'getObjectAcl') {
           $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT]. "?" . $options[self::OSS_SUB_RESOURCE];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] ."/". $options[self::OSS_OBJECT] ."?acl";

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ($options[self::OSS_FUNCTIONNAME] == 'putObjectAcl') {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_AMZ_ACL .':'. $options[self::OSS_ACL] . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT]. "?" . $options[self::OSS_SUB_RESOURCE];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
                self::OSS_ACL           => $options[self::OSS_ACL],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] ."/". $options[self::OSS_OBJECT] ."?acl";

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ( in_array($options[self::OSS_FUNCTIONNAME], array('getObjectMeta','doesObjectExist'))) {

            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] ."/".$options[self::OSS_OBJECT];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ( in_array($options[self::OSS_FUNCTIONNAME], array('getObject'))) {

            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] ."/". $options[self::OSS_OBJECT];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            if (is_dir(Config::OSS_FILE_DOWNLOAD)) {
                $file = Config::OSS_FILE_DOWNLOAD . $options[self::OSS_OBJECT];
                $fp = fopen($file,"w");
                $str_data = fwrite($fp,$request->get_response_body());
                fclose($fp);
            }

            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        } elseif ($options[self::OSS_FUNCTIONNAME] == 'putObject') {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_AMZ_ACL .':'. $options[self::OSS_ACL] . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
                self::OSS_ACL           => $options[self::OSS_ACL],
                self::OSS_CONTENT_MD5   => $options[self::OSS_CONTENT_MD5],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] ."/". $options[self::OSS_OBJECT];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD]);
            $request->set_body($options[self::OSS_CONTENT]);

            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }

            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());

        } elseif ($options[self::OSS_FUNCTIONNAME] == 'deleteObject') {
            $string_to_sign = $options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT];

            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            $headerArr = array(
                self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
                self::OSS_DATE          => $gmtDate,
                self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
             );

            $url = $this->endpoint . "/v1/storage/oss/". $options[self::OSS_BUCKET] . "/". $options[self::OSS_OBJECT];

            $request = new RequestCore($url,null,null,$headerArr);
            $request->set_method($options[self::OSS_METHOD] );
            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());


        } elseif ($options[self::OSS_FUNCTIONNAME] == 'signRtmpUrl') {
            $string_to_sign=$options[self::OSS_METHOD] . self::OSS_LINE_FEED . self::OSS_LINE_FEED . $options[self::OSS_CONTENT_TYPE] . self::OSS_LINE_FEED . $gmtDate . self::OSS_LINE_FEED . self::OSS_SLASH . $options[self::OSS_BUCKET] . self::OSS_SLASH . $options[self::OSS_OBJECT];

            $headerArr = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret,true)); 
            // $headerArr = array(
            //     self::OSS_AUTHORIZATION => 'AWS '. $this->accessKeyId .':'.$signature,
            //     self::OSS_DATE          => $gmtDate,
            //     self::OSS_CONTENT_TYPE  => $options[self::OSS_CONTENT_TYPE],
            //  );
        }


        return $headerArr;

    }

    /**
     * Validates bucket parameter
     *
     * @param string $bucket
     * @param string $errMsg
     * @throws OssException
     */
    private function precheckBucket($bucket, $errMsg = 'bucket is not allowed empty')
    {
        OssUtil::throwOssExceptionWithMessageIfEmpty($bucket, $errMsg);
    }


    /**
     * validates object parameter
     *
     * @param string $object
     * @throws OssException
     */
    private function precheckObject($object)
    {
        OssUtil::throwOssExceptionWithMessageIfEmpty($object, "object name is empty");
    }


    /**
     * validates options. Create a empty array if it's NULL.
     *
     * @param array $options
     * @throws OssException
     */
    private function precheckOptions(&$options)
    {
        OssUtil::validateOptions($options);
        if (!$options) {
            $options = array();
        }
    }


    /**
     * Validates bucket,options parameters and optionally validate object parameter.
     *
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $isCheckObject
     */
    private function precheckCommon($bucket, $object, &$options, $isCheckObject = true)
    {
        if ($isCheckObject) {
            $this->precheckObject($object);
        }
        $this->precheckOptions($options);
        $this->precheckBucket($bucket);
    }


    // Constants for Life cycle
    const OSS_LIFECYCLE_EXPIRATION = "Expiration";
    const OSS_LIFECYCLE_TIMING_DAYS = "Days";
    const OSS_LIFECYCLE_TIMING_DATE = "Date";
    //OSS Internal constants
    const OSS_BUCKET = 'bucket';
    const OSS_OBJECT = 'object';
    const OSS_HEADERS = OssUtil::OSS_HEADERS;
    const OSS_METHOD = 'method';
    const OSS_QUERY = 'query';
    const OSS_BASENAME = 'basename';
    const OSS_FUNCTIONNAME = 'function-name';
    const OSS_MAX_KEYS = 'max-keys';
    const OSS_UPLOAD_ID = 'uploadId';
    const OSS_PART_NUM = 'partNumber';
    const OSS_COMP = 'comp';
    const OSS_LIVE_CHANNEL_STATUS = 'status';
    const OSS_LIVE_CHANNEL_START_TIME = 'startTime';
    const OSS_LIVE_CHANNEL_END_TIME = 'endTime';
    const OSS_POSITION = 'position';
    const OSS_MAX_KEYS_VALUE = 100;
    const OSS_LINE_FEED = PHP_EOL;
    // const OSS_MAX_OBJECT_GROUP_VALUE = OssUtil::OSS_MAX_OBJECT_GROUP_VALUE;
    // const OSS_MAX_PART_SIZE = OssUtil::OSS_MAX_PART_SIZE;
    // const OSS_MID_PART_SIZE = OssUtil::OSS_MID_PART_SIZE;
    // const OSS_MIN_PART_SIZE = OssUtil::OSS_MIN_PART_SIZE;
    const OSS_FILE_SLICE_SIZE = 8192;
    const OSS_PREFIX = 'prefix';
    const OSS_DELIMITER = 'delimiter';
    const OSS_MARKER = 'marker';
    const OSS_ACCEPT_ENCODING = 'Accept-Encoding';
    const OSS_CONTENT_MD5 = 'Content-Md5';
    const OSS_SELF_CONTENT_MD5 = 'x-oss-meta-md5';
    const OSS_CONTENT_TYPE = 'Content-Type';
    const OSS_CONTENT_LENGTH = 'Content-Length';
    const OSS_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const OSS_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    const OSS_IF_MATCH = 'If-Match';
    const OSS_IF_NONE_MATCH = 'If-None-Match';
    const OSS_CACHE_CONTROL = 'Cache-Control';
    const OSS_EXPIRES = 'Expires';
    const OSS_PREAUTH = 'preauth';
    const OSS_CONTENT_COING = 'Content-Coding';
    const OSS_CONTENT_DISPOSTION = 'Content-Disposition';
    const OSS_RANGE = 'range';
    const OSS_ETAG = 'etag';
    const OSS_LAST_MODIFIED = 'lastmodified';
    const OS_CONTENT_RANGE = 'Content-Range';
    const OSS_CONTENT = OssUtil::OSS_CONTENT;
    const OSS_BODY = 'body';
    const OSS_LENGTH = OssUtil::OSS_LENGTH;
    const OSS_HOST = 'Host';
    const OSS_DATE = 'Date';
    const OSS_SLASH = "/";
    const OSS_AUTHORIZATION = 'Authorization';
    // const OSS_FILE_DOWNLOAD = '/Users/zhangyifeng/Documents/xinnet_code/';
    const OSS_FILE_UPLOAD = 'fileUpload';
    const OSS_PART_SIZE = 'partSize';
    const OSS_SEEK_TO = 'seekTo';
    const OSS_SIZE = 'size';
    const OSS_QUERY_STRING = 'query_string';
    const OSS_SUB_RESOURCE = 'sub_resource';
    const OSS_DEFAULT_PREFIX = 'x-oss-';
    const OSS_CHECK_MD5 = 'checkmd5';
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    const OSS_SYMLINK_TARGET = 'x-oss-symlink-target';
    const OSS_SYMLINK = 'symlink';
    const OSS_HTTP_CODE = 'http_code';
    const OSS_REQUEST_ID = 'x-oss-request-id';
    const OSS_INFO = 'info';
    const OSS_STORAGE = 'storage';
    const OSS_RESTORE = 'restore';
    const OSS_STORAGE_STANDARD = 'Standard';
    const OSS_STORAGE_IA = 'IA';
    const OSS_STORAGE_ARCHIVE = 'Archive';

    //private URLs
    const OSS_URL_ACCESS_KEY_ID = 'OSSAccessKeyId';
    const OSS_URL_EXPIRES = 'Expires';
    const OSS_URL_SIGNATURE = 'Signature';
    //HTTP METHOD
    const OSS_HTTP_GET = 'GET';
    const OSS_HTTP_PUT = 'PUT';
    const OSS_HTTP_HEAD = 'HEAD';
    const OSS_HTTP_POST = 'POST';
    const OSS_HTTP_DELETE = 'DELETE';
    const OSS_HTTP_OPTIONS = 'OPTIONS';
    //Others
    const OSS_ACL = 'x-oss-acl';
    const OSS_AMZ_ACL = 'x-amz-acl';
    const OSS_OBJECT_ACL = 'x-oss-object-acl';
    const OSS_OBJECT_GROUP = 'x-oss-file-group';
    const OSS_MULTI_PART = 'uploads';
    const OSS_MULTI_DELETE = 'delete';
    const OSS_OBJECT_COPY_SOURCE = 'x-oss-copy-source';
    const OSS_OBJECT_COPY_SOURCE_RANGE = "x-oss-copy-source-range";
    const OSS_PROCESS = "x-oss-process";
    const OSS_CALLBACK = "x-oss-callback";
    const OSS_CALLBACK_VAR = "x-oss-callback-var";
    //Constants for STS SecurityToken
    const OSS_SECURITY_TOKEN = "x-oss-security-token";
    const OSS_ACL_TYPE_PRIVATE = 'private';
    const OSS_ACL_TYPE_PUBLIC_READ = 'public-read';
    const OSS_ACL_TYPE_PUBLIC_READ_WRITE = 'public-read-write';
    const OSS_ENCODING_TYPE = "encoding-type";
    const OSS_ENCODING_TYPE_URL = "url";

    // Domain Types
    const OSS_HOST_TYPE_NORMAL = "normal";
    const OSS_HOST_TYPE_IP = "ip";  
    const OSS_HOST_TYPE_SPECIAL = 'special'; 
    const OSS_HOST_TYPE_CNAME = "cname"; 
    //OSS ACL array
    static $OSS_ACL_TYPES = array(
        self::OSS_ACL_TYPE_PRIVATE,
        self::OSS_ACL_TYPE_PUBLIC_READ,
        self::OSS_ACL_TYPE_PUBLIC_READ_WRITE
    );
    // OssClient version information
    const OSS_NAME = "ppcloud-sdk-php";
    const OSS_VERSION = "0.1.0";
    const OSS_BUILD = "20200103";
    const OSS_AUTHOR = "";
    const OSS_OPTIONS_ORIGIN = 'Origin';
    const OSS_OPTIONS_REQUEST_METHOD = 'Access-Control-Request-Method';
    const OSS_OPTIONS_REQUEST_HEADERS = 'Access-Control-Request-Headers';

    //use ssl flag
    private $useSSL = false;
    private $maxRetries = 3;
    private $redirects = 0;

    // user's domain type. It could be one of the four: OSS_HOST_TYPE_NORMAL, OSS_HOST_TYPE_IP, OSS_HOST_TYPE_SPECIAL, OSS_HOST_TYPE_CNAME
    private $hostType = self::OSS_HOST_TYPE_NORMAL;
    private $requestUrl;
    private $requestProxy = null;
    private $accessKeyId;
    private $accessKeySecret;
    private $hostname;
    private $securityToken;
    private $enableStsInUrl = false;
    private $timeout = 0;
    private $connectTimeout = 0;
}
