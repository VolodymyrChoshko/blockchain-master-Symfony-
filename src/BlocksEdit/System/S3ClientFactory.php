<?php
namespace BlocksEdit\System;

use Aws\S3\S3Client;
use BlocksEdit\Config\Config;

/**
 * Class S3ClientFactory
 */
class S3ClientFactory
{
    /**
     * @param Config $config
     *
     * @return S3Client
     */
    public static function create(Config $config): S3Client
    {
        return new S3Client($config->aws);
    }
}
