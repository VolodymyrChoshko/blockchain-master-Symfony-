<?php
namespace BlocksEdit\Integrations\Services\Klaviyo;

use Klaviyo\Exception\KlaviyoException;
use Klaviyo\KlaviyoAPI;

/**
 * Class Api
 */
class Api extends KlaviyoAPI
{
    /**
     * Make private v1 API request
     *
     * @param string $path Endpoint to call
     * @param array $options API params to add to request
     * @param string $method HTTP method for request
     * @return mixed
     *
     * @throws KlaviyoException
     */
    protected function v1Request($path, $options = [], $method = self::HTTP_GET )
    {
        return parent::v1Request($path, $options, $method);
    }

    /**
     * Setup authentication for Klaviyo API V1 request
     *
     * @param mixed $params
     * @return array
     */
    protected function v1Auth($params): array
    {
        if (!isset($params['form_params'])) {
            $params = [
                self::QUERY => array_merge(
                    $params,
                    [self::API_KEY_PARAM => $this->private_key]
                )
            ];
        } else {
            $params[self::QUERY] = [self::API_KEY_PARAM => $this->private_key];
        }

        return $this->setUserAgentHeader($params);
    }
}
