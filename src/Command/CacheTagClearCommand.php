<?php
namespace Command;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Exception;
use Redis;

/**
 * Class CacheTagClearCommand
 */
class CacheTagClearCommand extends Command
{
    static $name = 'cache:tag:clear';

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Deletes the values for a cache tag.';
    }

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param Redis          $redis
     */
    public function __construct(CacheInterface $cache, Redis $redis)
    {
        $this->cache = $cache;
        $this->redis = $redis;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $tag = $input->read('Cache tag class');
        if (!$tag) {
            return;
        }
        if ($tag === '*') {
            $this->clearAllTags($output);
        } else {
            $value = $input->read('Cache tag value', null);
            if ($value === null) {
                return;
            }

            $className = "Tag\\$tag";
            $obj       = new $className(trim($value));
            $this->cache->deleteByTag($obj);
        }

        $output->writeLine('Done');
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function clearAllTags(OutputInterface $output)
    {
        $keys = $this->redis->keys('be-cache-tag:*');
        foreach($keys as $key) {
            try {
                $output->writeLine('Deleting %s.', $key);
                $members = $this->redis->sMembers($key);
                if ($members) {
                    foreach($members as $member) {
                        $output->writeLine("\t$member");
                    }
                    $this->redis->del($members);
                }
                $this->redis->del($key);
            } catch (Exception $e) {
                $output->errorLine($e->getMessage());
            }
        }
    }
}
