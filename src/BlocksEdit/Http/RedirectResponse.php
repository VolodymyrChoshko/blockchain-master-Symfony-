<?php
namespace BlocksEdit\Http;

/**
 * Class RedirectResponse
 */
class RedirectResponse extends Response
{
    /**
     * Constructor
     *
     * @param string $url
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct($url, $statusCode = StatusCodes::MOVED_PERMANENTLY, array $headers = [])
    {
        parent::__construct($url, $statusCode, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        $location = $this->content;
        header("Location: ${location}");

        return true;
    }
}
