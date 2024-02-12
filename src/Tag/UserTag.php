<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class UserTag
 */
class UserTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param int $uid
     */
    public function __construct(int $uid)
    {
        parent::__construct("user:$uid");
    }
}
