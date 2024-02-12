<?php
namespace BlocksEdit\Cache;

use BlocksEdit\Config\Config;
use InvalidArgumentException;
use Redis;

/**
 * Class RedisCache
 */
class RedisCache implements CacheInterface
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $isBatching = false;

    /**
     * @var array
     */
    protected $batch = [];

    /**
     * @var Redis|null
     */
    protected $pipe = null;

    /**
     * Constructor
     *
     * @param Redis  $redis
     * @param Config $config
     */
    public function __construct(Redis $redis, Config $config)
    {
        $this->redis  = $redis;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $expiration = self::ONE_MONTH, array $tags = []): bool
    {
        if (!$this->config->cacheEnabled) {
            return true;
        }
        if ($expiration > self::ONE_MONTH) {
            $expiration = self::ONE_MONTH;
        }
        if ($this->pipe) {
            $this->pipe->setex($key, $expiration, serialize($value));
        } else if ($this->isBatching) {
            $this->batch[] = compact('key', 'value', 'expiration', 'tags');
        } else {
            $this->redis->setex($key, $expiration, serialize($value));
        }
        $this->setCachedTags($tags, $key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $defaultValue = null)
    {
        if (!$this->config->cacheEnabled) {
            return $defaultValue;
        }
        $value = $this->redis->get($key);
        if ($value === false) {
            return $defaultValue;
        }

        return @unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        if (!$this->config->cacheEnabled) {
            return false;
        }
        $value = $this->redis->get($key);
        if (!$value) {
            return false;
        }

        return (bool)@unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if (!$this->config->cacheEnabled) {
            return true;
        }
        $this->redis->del($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByTag(CacheTag $tag): bool
    {
        if (!$this->config->cacheEnabled) {
            return true;
        }
        $members = $this->redis->sMembers('be-cache-tag:' . $tag->getValue());
        if ($members) {
            $this->redis->del($members);
        }
        $this->deleteByTags($tag->getTags());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByTags(array $tags): bool
    {
        if (!$this->config->cacheEnabled) {
            return true;
        }
        foreach($tags as $tag) {
            if (!($tag instanceof CacheTag)) {
                throw new InvalidArgumentException(
                    "Instances passed to CacheInterface::deleteByTags() must be instances of CacheTag."
                );
            }
            $this->deleteByTag($tag);
        }

        return true;
    }

    /**
     * @param CacheTag[] $tags
     * @param string $key
     *
     * @return void
     */
    protected function setCachedTags(array $tags, string $key)
    {
        $redis = $this->pipe ?? $this->redis;
        foreach($tags as $tag) {
            $tagKey = 'be-cache-tag:' . $tag->getValue();
            $redis->sAdd($tagKey, $key);
            $redis->expire($tagKey, self::ONE_MONTH + 3600);
            $this->setCachedTags($tag->getTags(), $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function batchStart(): bool
    {
        $this->isBatching = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function batchCommit(): bool
    {
        try {
            $this->pipe = $this->redis->pipeline();
            foreach ($this->batch as $args) {
                $this->set($args['key'], $args['value'], $args['expiration'], $args['tags']);
            }
            $this->pipe->exec();
        } finally {
            $this->pipe       = null;
            $this->batch      = [];
            $this->isBatching = false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function batchRollback(): bool
    {
        $this->pipe       = null;
        $this->batch      = [];
        $this->isBatching = false;

        return true;
    }
}
