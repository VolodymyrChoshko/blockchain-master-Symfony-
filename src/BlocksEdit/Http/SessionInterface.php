<?php
namespace BlocksEdit\Http;

use BlocksEdit\Http\Exception\BadRequestException;

/**
 * Class Session
 */
interface SessionInterface
{
    /**
     * @param string $rootDomain
     *
     * @return $this
     */
    public function setRootDomain(string $rootDomain): SessionInterface;

    /**
     * @param array $iniSettings
     *
     * @return $this
     */
    public function setIniSettings(array $iniSettings): SessionInterface;

    /**
     * @return int[]
     */
    public function getIniSettings(): array;

    /**
     * Start the session
     *
     * @return bool
     */
    public function start(): bool;

    /**
     *
     */
    public function destroy();

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param string $key
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function getOrBadRequest(string $key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function set(string $key, $value): bool;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove(string $key): bool;
}
