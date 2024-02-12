<?php
namespace BlocksEdit\Http;

use BlocksEdit\Http\Exception\BadRequestException;
use BlocksEdit\System\ParameterBag as BaseParameterBag;

/**
 * Class ParameterBag
 */
class ParameterBag extends BaseParameterBag
{
    /**
     * @param string $key
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function getOrBadRequest(string $key)
    {
        if (!$this->has($key)) {
            throw new BadRequestException();
        }

        return $this->get($key);
    }

    /**
     * @param string $key
     * @param array  $default
     *
     * @return array
     */
    public function getArray(string $key, array $default = [])
    {
        if (!$this->has($key)) {
            return $default;
        }

        return (array)$this->get($key);
    }
}
