<?php
namespace BlocksEdit\Service;

/**
 * Class Message
 */
class Message
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * Constructor
     *
     * @param mixed $data
     * @param array $meta
     */
    public function __construct($data, array $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
}
