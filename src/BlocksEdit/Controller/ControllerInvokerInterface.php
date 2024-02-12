<?php
namespace BlocksEdit\Controller;

use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Http\Request;

/**
 * Class ControllerInvoker
 */
interface ControllerInvokerInterface
{
    /**
     * @param Request $request
     * @param string|object $controller
     * @param string  $method
     *
     * @return mixed
     * @throws StatusCodeException
     */
    public function invokeAction(Request $request, $controller, string $method);
}
