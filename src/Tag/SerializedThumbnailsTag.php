<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class SerializedThumbnailsTag
 */
class SerializedThumbnailsTag extends CacheTag
{
    /**
     * Constructor
     *
     * @param string $type
     * @param int    $id
     */
    public function __construct(string $type, int $id)
    {
        parent::__construct("serialized:thumbnails:$type:$id");
    }
}
