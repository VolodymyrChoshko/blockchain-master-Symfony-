<?php
namespace BlocksEdit\Integrations\Services\SalesForce;

/**
 * Class PagedItems
 */
class PagedItems
{
    /**
     * @var int
     */
    protected $count;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var string
     */
    protected $url;

    /**
     * Constructor
     *
     * @param array $data
     * @param string $url
     */
    public function __construct(array $data, string $url)
    {
        $this->count    = (int)$data['count'];
        $this->page     = (int)$data['page'];
        $this->pageSize = (int)$data['pageSize'];
        $this->items    = $data['items'];
        $this->url      = $url;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function hasMore(): bool
    {
        $fetched = (($this->page - 1) * $this->pageSize);
        $fetched += count($this->items);

        return $fetched < $this->count;
    }
}
