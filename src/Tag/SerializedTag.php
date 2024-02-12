<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class SerializedTag
 */
class SerializedTag extends CacheTag
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('serialized');
    }
}
