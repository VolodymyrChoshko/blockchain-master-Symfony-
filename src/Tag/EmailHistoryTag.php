<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class EmailHistoryTag
 */
class EmailHistoryTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param int $tid
     */
    public function __construct(int $tid)
    {
        parent::__construct("template:$tid");
    }
}
