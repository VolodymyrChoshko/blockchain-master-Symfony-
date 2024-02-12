<?php
namespace BlocksEdit\Http;

use BlocksEdit\View\View;

/**
 * Class Response
 */
class Response implements ResponseInterface
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Constructor
     *
     * @param string|View $content
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct($content = '', $statusCode = StatusCodes::OK, array $headers = [])
    {
        $this->content    = (string)$content;
        $this->statusCode = $statusCode;
        $this->headers    = $headers;

        if (empty($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function sendHeaders()
    {
        http_response_code($this->getStatusCode());
        foreach($this->getHeaders() as $key => $value) {
            header("${key}: ${value}");
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function sendContent()
    {
        echo $this->content;

        return true;
    }
}
