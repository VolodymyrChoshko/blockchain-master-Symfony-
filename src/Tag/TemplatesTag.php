<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class TemplatesTag
 */
class TemplatesTag extends CacheTag
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('templates');
    }
}
