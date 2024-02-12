<?php
namespace BlocksEdit\Http;

/**
 * Interface Response
 */
interface ResponseInterface
{
    /**
     * @return string
     */
    public function getContent();

    /**
     * @return int
     */
    public function getStatusCode();

    /**
     * @return array
     */
    public function getHeaders();

    /**
     * @return bool
     */
    public function send();

    /**
     * @return bool
     */
    public function sendHeaders();

    /**
     * @return bool
     */
    public function sendContent();
}
