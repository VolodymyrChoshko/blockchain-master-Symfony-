<?php
namespace BlocksEdit\Integrations\Services\Klaviyo;

use Klaviyo\Klaviyo;
use Klaviyo\Exception\KlaviyoException;

/**
 * Class Client
 *
 * @property Templates $templates
 * @property Campaigns $campaigns
 */
class Client extends Klaviyo
{
    /**
     * Dynamically retrieve the corresponding API service and
     * save as property for re-use.
     *
     * @param string $api API service
     *
     * @return mixed
     * @throws KlaviyoException
     */
    public function __get($api)
    {
        $service = __NAMESPACE__ . '\\' . ucfirst($api);
        if (class_exists($service)) {
            $this->$api = new $service($this->public_key, $this->private_key);

            return $this->$api;
        }

        $service = 'Klaviyo\\' . ucfirst($api);
        if (class_exists($service)) {
            $this->$api = new $service($this->public_key, $this->private_key);

            return $this->$api;
        }

        throw new KlaviyoException('Sorry, ' . $api . ' is not a valid Klaviyo API.');
    }
}
