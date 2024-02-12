<?php
namespace BlocksEdit\Html;

use BlocksEdit\Http\Request;

/**
 * Class NonceGenerator
 */
interface NonceGeneratorInterface
{
    /**
     * @param string $form
     * @param int    $expiration
     *
     * @return string
     */
    public function generate(string $form, $expiration = 3600);

    /**
     * @param string $form
     * @param string $nonce
     *
     * @return bool
     */
    public function verify(string $form, string $nonce);

    /**
     * @param string  $form
     * @param Request $request
     * @param string  $key
     *
     * @return bool
     */
    public function verifyRequest(string $form, Request $request, $key = 'token');
}
