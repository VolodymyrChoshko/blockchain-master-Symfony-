<?php
namespace BlocksEdit\Cache;

/**
 * Class MemoryCache
 */
class MemoryCache implements CacheInterface
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $expiration = self::ONE_MONTH, array $tags = []): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $defaultValue = null)
    {
        if (!isset($this->values[$key])) {
            return $defaultValue;
        }

        return $this->values[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByTag(CacheTag $tag): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByTags(array $tags): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function batchStart(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function batchCommit(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function batchRollback(): bool
    {
        return true;
    }
}
