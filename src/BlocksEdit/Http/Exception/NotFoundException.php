<?php
namespace BlocksEdit\Http\Exception;

use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Http\StatusCodes;
use Throwable;

/**
 * Class NotFoundException
 */
class NotFoundException extends StatusCodeException
{
    /**
     * Constructor
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = StatusCodes::NOT_FOUND, Throwable $previous = null)
    {
        if (!$message) {
            $message = StatusCodes::getMessageForCode($code);
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'errors/404.html.twig';
    }
}
