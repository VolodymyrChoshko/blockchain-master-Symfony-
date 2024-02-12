<?php
namespace BlocksEdit\Config;

use BlocksEdit\System\Required;

/**
 *
 */
trait ConfigTrait
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @Required()
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
