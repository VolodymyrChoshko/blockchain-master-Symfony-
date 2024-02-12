<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class SerializedTemplateTag
 */
class SerializedTemplateTag extends CacheTag
{
    /**
     * @var CacheTag[]
     */
    protected $tags = [];

    /**
     * Constructor
     *
     * @param int $tid
     */
    public function __construct(int $tid)
    {
        parent::__construct("serialized:template:$tid");

        $this->tags = [
            new TemplatesTag(),
            new TemplateTag($tid),
            new SerializedTag()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
