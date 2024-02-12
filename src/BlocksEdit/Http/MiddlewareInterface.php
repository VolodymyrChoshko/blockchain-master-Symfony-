<?php
namespace BlocksEdit\Http;

/**
 * Interface MiddlewareInterface
 *
 * @package App\Http
 */
interface MiddlewareInterface
{
    /**
     * @return int
     */
    public function getPriority(): int;
}
