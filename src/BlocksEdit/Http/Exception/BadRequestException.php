<?php
namespace BlocksEdit\Http\Exception;

use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Http\StatusCodes;
use Throwable;

/**
 * Class BadRequestException
 */
class BadRequestException extends StatusCodeException
{
    /**
     * Constructor
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = StatusCodes::BAD_REQUEST, Throwable $previous = null)
    {
        if (!$message) {
            $message = StatusCodes::getMessageForCode($code);
        }
        parent::__construct($message, $code, $previous);
    }
}
