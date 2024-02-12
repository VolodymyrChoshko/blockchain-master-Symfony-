<?php
namespace BlocksEdit\Cache;

/**
 * Class CacheTag
 */
class CacheTag
{
    /**
     * @var string
     */
    protected $value = '';

    /**
     * Constructor
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return [];
    }
}
