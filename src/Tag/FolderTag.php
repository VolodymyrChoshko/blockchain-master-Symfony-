<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class FolderTag
 */
class FolderTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param int $fid
     */
    public function __construct(int $fid)
    {
        parent::__construct("folder:$fid");
    }
}
