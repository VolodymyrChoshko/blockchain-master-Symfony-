<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class EmailTag
 */
class EmailTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param int $eid
     */
    public function __construct(int $eid)
    {
        parent::__construct("email:$eid");
    }
}
