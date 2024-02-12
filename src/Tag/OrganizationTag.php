<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class OrganizationTag
 */
class OrganizationTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param int $oid
     */
    public function __construct(int $oid)
    {
        parent::__construct("organization:$oid");
    }
}
