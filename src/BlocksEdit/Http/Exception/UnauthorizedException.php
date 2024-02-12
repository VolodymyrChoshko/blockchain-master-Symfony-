<?php
namespace BlocksEdit\Http\Exception;

use BlocksEdit\Http\StatusCodes;
use Throwable;

/**
 * Class UnauthorizedException
 */
class UnauthorizedException extends StatusCodeException
{
    /**
     * Constructor
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = StatusCodes::UNAUTHORIZED, Throwable $previous = null)
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
        return 'errors/unauthorized.html.twig';
    }
}
