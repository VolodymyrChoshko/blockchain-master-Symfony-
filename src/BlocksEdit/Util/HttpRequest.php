<?php
namespace BlocksEdit\Util;

use GuzzleHttp\Client;

/**
 * Class HttpRequest
 */
class HttpRequest
{
    /**
     * @param string $url
     * @param string $filename
     *
     * @return string
     */
    public function get(string $url, string $filename = ''): string
    {
        $client = new Client([
            'verify' => false
        ]);
        $options = [
            'timeout' => 5
        ];
        if ($filename) {
            $options['sink'] = $filename;
        }
        $resp = $client->get($url, $options);
        if ($filename) {
            return '';
        }

        return (string)$resp->getBody();
    }
}
