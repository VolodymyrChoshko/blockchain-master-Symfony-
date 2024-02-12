<?php
namespace Tag;

use BlocksEdit\Cache\CacheTag;

/**
 * Class SerializedHistoryTag
 */
class SerializedHistoryTag extends CacheTag
{
    /**
     * @var CacheTag[]
     */
    protected $tags = [];

    /**
     * Constructor
     *
     * @param string $type
     * @param int    $id
     */
    public function __construct(string $type, int $id)
    {
        parent::__construct("serialized:history:$type:$id");

        if ($type === 'template') {
            $this->tags = [
                new TemplateHistoryTag($id)
            ];
        } else if ($type === 'email') {
            $this->tags = [
                new EmailHistoryTag($id)
            ];
        }
        $this->tags[] = new SerializedTag();
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
