<?php
namespace BlocksEdit\Http;

use BlocksEdit\Http\Exception\BadRequestException;
use SessionHandlerInterface;

/**
 * Class Session
 */
class Session implements SessionInterface
{
    /**
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * @var int[]
     */
    protected $iniSettings = [
        // Prevents javascript XSS attacks aimed to steal the session ID
        'session.cookie_httponly'  => 1,
        // Session ID cannot be passed through URLs
        'session.use_only_cookies' => 1,
        // Uses a secure connection (HTTPS) if possible
        'session.cookie_secure'    => 1
    ];

    /**
     * Constructor
     *
     * @param SessionHandlerInterface $sessionHandler
     */
    public function __construct(SessionHandlerInterface $sessionHandler)
    {
        $this->handler = $sessionHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function setIniSettings(array $iniSettings): SessionInterface
    {
        $this->iniSettings = $iniSettings;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIniSettings(): array
    {
        return $this->iniSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function setRootDomain(string $rootDomain): SessionInterface
    {
        ini_set('session.cookie_domain', '.' . $rootDomain);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function start(): bool
    {
        foreach($this->iniSettings as $key => $value) {
            ini_set($key, $value);
        }
        session_set_save_handler($this->handler);
        session_start();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null)
    {
        if (!isset($_SESSION[$key])) {
            return $default;
        }

        return $_SESSION[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrBadRequest(string $key)
    {
        if (!$this->has($key)) {
            throw new BadRequestException();
        }

        return $this->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value): bool
    {
        $_SESSION[$key] = $value;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): bool
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }

        return true;
    }
}
