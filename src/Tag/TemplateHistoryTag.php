<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class TemplateHistoryTag
 */
class TemplateHistoryTag extends CacheTag
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
