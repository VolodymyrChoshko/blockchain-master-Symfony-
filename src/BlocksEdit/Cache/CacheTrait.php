<?php
namespace BlocksEdit\Cache;

use BlocksEdit\System\Required;

/**
 *
 */
trait CacheTrait
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @Required()
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
}
