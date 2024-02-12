<?php
namespace BlocksEdit\Html;

use BlocksEdit\Http\Request;
use Redis;

/**
 * Class NonceGenerator
 */
class NonceGenerator implements NonceGeneratorInterface
{
    const PREFIX = 'nonce';

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var FlasherInterface
     */
    protected $flasher;

    /**
     * Constructor
     *
     * @param Redis            $redis
     * @param FlasherInterface $flasher
     */
    public function __construct(Redis $redis, FlasherInterface $flasher)
    {
        $this->redis   = $redis;
        $this->flasher = $flasher;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(string $form, $expiration = 3600)
    {
        $key   = $this->createKey($form);
        $nonce = $this->createNonce();
        $this->redis->setex($key, $expiration, $nonce);

        return $nonce;
    }

    /**
     * {@inheritDoc}
     */
    public function verify(string $form, string $nonce)
    {
        $key   = $this->createKey($form);
        $found = $this->redis->get($key);
        $this->redis->del($key);

        return ($found && $nonce && $found === $nonce);
    }

    /**
     * {@inheritDoc}
     */
    public function verifyRequest(string $form, Request $request, $key = 'token')
    {
        $token = $request->post->get($key);
        if (!$token) {
            $token = $request->query->get($key);
        }
        if (!$this->verify($form, $token)) {
            $this->flasher->error('Your session has expired. Please try again.');

            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function createNonce()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * @param string $form
     *
     * @return string
     */
    protected function createKey(string $form)
    {
        return sprintf('%s:%s', self::PREFIX, $form);
    }
}
