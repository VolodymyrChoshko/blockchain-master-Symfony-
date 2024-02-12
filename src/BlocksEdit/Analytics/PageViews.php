<?php
namespace BlocksEdit\Analytics;

use DateTime;
use Redis;

/**
 * Class PageViews
 */
class PageViews
{
    const PAGE_DEFAULT = 'default';
    const PAGE_BUILDER = 'builder';

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $page
     *
     * @return int
     */
    public function incrementPageView(string $page = self::PAGE_DEFAULT): int
    {
        $date = new DateTime();
        $this->redis->incr(sprintf('analytics:pageviews:%s:total', $page));

        return $this->redis->incr(
            sprintf('analytics:pageviews:%s:%s', $page, $date->format('Y:m:d'))
        );
    }

    /**
     * @param string $page
     *
     * @return int
     */
    public function getPageViewsTotal(string $page = self::PAGE_DEFAULT): int
    {
        return (int)$this->redis->get(sprintf('analytics:pageviews:%s:total', $page));
    }

    /**
     * @param DateTime|null $date
     * @param string        $page
     *
     * @return int
     */
    public function getPageViews(?DateTime $date = null, string $page = self::PAGE_DEFAULT): int
    {
        if (!$date) {
            $date = new DateTime();
        }
        $key = sprintf('analytics:pageviews:%s:%s', $page, $date->format('Y:m:d'));

        return (int)$this->redis->get($key);
    }

    /**
     * @param DateTime $start
     * @param string   $page
     *
     * @return int
     */
    public function getPageViewsSince(DateTime $start, string $page = self::PAGE_DEFAULT): int
    {
        $count = 0;
        $now   = new DateTime();
        $begin = clone $start;
        $diff  = $now->diff($start)->format('%a');
        for($i = 0; $i < $diff; $i++) {
            $begin->add(new \DateInterval('P1D'));
            $key    = sprintf('analytics:pageviews:%s:%s', $page, $begin->format('Y:m:d'));
            $count += (int)$this->redis->get($key);

        }

        return $count;
    }
}
