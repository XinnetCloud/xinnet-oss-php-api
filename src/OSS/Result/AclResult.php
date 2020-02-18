<?php

namespace OSS\Result;

use OSS\Core\OssException;

/**
 * The type of the return value of getBucketAcl, it wraps the data parsed from xml.
 *
 * @package OSS\Result
 */
class AclResult extends Result
{
    /**
     * @return string
     * @throws OssException
     */


    // protected function parseDataFromResponse()
    // {
    //     $content = $this->rawResponse->body;
    //     if (empty($content)) {
    //         throw new OssException("body is null");
    //     }
    //     $xml = simplexml_load_string($content);
    //     if (isset($xml->AccessControlList->Grant)) {
    //         return strval($xml->AccessControlList->Grant);
    //     } else {
    //         throw new OssException("xml format exception");
    //     }
    // }

    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if (empty($content)) {
            throw new OssException("body is null");
        }

        $xml = simplexml_load_string($content);

        if (isset($xml->AccessControlList->Grant)) {
            $num = 0;
            $acl = "";
            foreach ($xml->AccessControlList->Grant as $permission) {
                if(strval($permission->Permission) == 'READ') {
                    $num += 1;
                }

                if(strval($permission->Permission) == 'WRITE') {
                    $num += 2;
                }
            }

            switch ($num) {
                case 3:
                    $acl = 'public-read-write';
                    break;
           
                case 1:
                    $acl = 'public-read';
                    break;

                default:
                    $acl = 'private';
                    break;
            }

            // print_r($xml->AccessControlList->Grant);
            return strval($acl);
        } else {
            throw new OssException("xml format exception, data error");
        }

    }

}