<?php
namespace BlocksEdit\Service;

/**
 * Class AsyncRequests
 */
class AsyncRequests
{
    /**
     * @var array
     */
    protected $requests = [];

    /**
     * @param string $html
     * @param array  $options
     *
     * @return $this
     */
    public function add(string $html, array $options = []): AsyncRequests
    {
        $this->requests[] = [
            'html'    => $html,
            'options' => $options
        ];

        return $this;
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return $this
     */
    public function addUrl(string $url, array $options = []): AsyncRequests
    {
        $this->requests[] = [
            'url'     => $url,
            'options' => $options
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getRequests(): array
    {
        return $this->requests;
    }
}
