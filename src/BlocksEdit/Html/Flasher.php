<?php
namespace BlocksEdit\Html;

use BlocksEdit\Http\SessionInterface;
use Exception;
use InvalidArgumentException;

/**
 * Class Flasher
 */
class Flasher implements FlasherInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * Constructor
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param SessionInterface $session
     *
     * @return FlasherInterface
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * {@inheritDoc}
     */
    public function success(string $message): bool
    {
        try {
            return $this->flash(self::FLASH_SUCCESS, $message);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $message): bool
    {
        try {
            return $this->flash(self::FLASH_ERROR, $message);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function flash($type, string $message = ''): bool
    {
        if (is_array($type) && isset($type['message'])) {
            $message = $type['message'];
            if ($type['error']) {
                $type = self::FLASH_ERROR;
            } else {
                $type = self::FLASH_SUCCESS;
            }
        }

        if (!in_array($type, [self::FLASH_SUCCESS, self::FLASH_ERROR])) {
            throw new InvalidArgumentException(
                "Invalid flash type ${type}."
            );
        }

        $messages = $this->initFlashMessages();
        $messages[$type][] = $message;
        $this->session->set(self::SESSION_KEY, $messages);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages(string $type): array
    {
        if (!in_array($type, [self::FLASH_SUCCESS, self::FLASH_ERROR])) {
            throw new InvalidArgumentException(
                "Invalid flash type ${type}."
            );
        }

        $messages    = $this->initFlashMessages();
        $newMessages = $messages[$type];
        $messages[$type] = [];
        $this->session->set(self::SESSION_KEY, $messages);

        return $newMessages;
    }

    /**
     * @return array[]
     */
    private function initFlashMessages()
    {
        $messages = $this->session->get(self::SESSION_KEY);
        if (!$messages) {
            $messages = [
                self::FLASH_SUCCESS => [],
                self::FLASH_ERROR   => []
            ];
            $this->session->set(self::SESSION_KEY, $messages);
        }

        return $messages;
    }
}
