<?php
namespace BlocksEdit\Integrations\Services\SalesForce;

/**
 * Class AccessToken
 */
class AccessToken
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $expires;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->token   = $params['access_token'];
        $this->expires = (int)$params['expires_in'];
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }
}
