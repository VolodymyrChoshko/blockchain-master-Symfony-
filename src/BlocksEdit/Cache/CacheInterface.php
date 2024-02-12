<?php
namespace BlocksEdit\Cache;

/**
 *
 */
interface CacheInterface
{
    const ONE_HOUR = 3600;
    const ONE_DAY = 86400;
    const ONE_WEEK = 604800;
    const ONE_MONTH = 2678400;

    /**
     * @param string      $key
     * @param mixed       $value
     * @param int         $expiration
     * @param CacheTag[]  $tags
     *
     * @return bool
     */
    public function set(string $key, $value, int $expiration = self::ONE_MONTH, array $tags = []): bool;

    /**
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get(string $key, $defaultValue = null);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * @param CacheTag $tag
     *
     * @return bool
     */
    public function deleteByTag(CacheTag $tag): bool;

    /**
     * @param CacheTag[] $tags
     *
     * @return bool
     */
    public function deleteByTags(array $tags): bool;

    /**
     * @return bool
     */
    public function batchStart(): bool;

    /**
     * @return bool
     */
    public function batchCommit(): bool;

    /**
     * @return bool
     */
    public function batchRollback(): bool;
}
