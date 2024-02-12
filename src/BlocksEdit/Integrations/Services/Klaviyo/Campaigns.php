<?php
namespace BlocksEdit\Integrations\Services\Klaviyo;

use Klaviyo\Exception\KlaviyoException;

/**
 * Class Campaigns
 */
class Campaigns extends Api
{
    /**
     * @return array
     * @throws KlaviyoException
     */
    public function getCampaigns(): array
    {
        $resp = $this->v1Request('campaigns');
        if (isset($resp['data'])) {
            return (array)$resp['data'];
        }

        return [];
    }
}
