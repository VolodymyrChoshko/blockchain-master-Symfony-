<?php
namespace BlocksEdit\Http;

/**
 * Class JsonResponse
 */
class JsonResponse extends Response
{
    /**
     * Constructor
     *
     * @param mixed $content
     * @param int   $statusCode
     * @param array $headers
     * @param bool  $isString
     */
    public function __construct($content, $statusCode = StatusCodes::OK, array $headers = [], $isString = false)
    {
        if ($content instanceof JsonResponse) {
            $statusCode = $content->getStatusCode();
            $content    = json_decode($content->getContent());
        }
        if (!$isString) {
            $content = json_encode($content);
        }
        if (empty($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json; charset=utf-8';
        }

        parent::__construct($content, $statusCode, $headers);
    }
}
