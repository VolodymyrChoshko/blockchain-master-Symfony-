<?php
namespace BlocksEdit\Media;

use Aws\S3\S3Client;
use BlocksEdit\Http\Mime;
use BlocksEdit\Config\Config;

/**
 * Class CDNFactory
 */
class CDNFactory
{
    /**
     * @param Config   $config
     * @param S3Client $client
     *
     * @return CDNInterface
     */
    public static function createInstance(Config $config, S3Client $client): CDNInterface
    {
        return new AmazonCDN($client, $config->cdn, new Mime());
    }
}
