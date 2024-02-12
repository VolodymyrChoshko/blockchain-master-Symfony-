<?php
namespace BlocksEdit\Html;

use BlocksEdit\Http\SessionInterface;
use Exception;

/**
 * Class Flasher
 */
interface FlasherInterface
{
    const FLASH_SUCCESS = 'success';
    const FLASH_ERROR   = 'error';
    const SESSION_KEY   = 'flasher';

    /**
     * @param SessionInterface $session
     *
     * @return FlasherInterface
     */
    public function setSession(SessionInterface $session);

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;

    /**
     * @param string $message
     *
     * @return bool
     */
    public function success(string $message): bool;

    /**
     * @param string $message
     *
     * @return bool
     */
    public function error(string $message): bool;

    /**
     * @param string|array $type
     * @param string       $message
     *
     * @return bool
     * @throws Exception
     */
    public function flash($type, string $message = ''): bool;

    /**
     * @param string $type
     *
     * @return array
     */
    public function getMessages(string $type): array;
}
