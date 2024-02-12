<?php
namespace BlocksEdit\Security;

use Exception;

/**
 * Class OpenSSL
 */
class OpenSSL
{
    /**
     * @var string
     */
    protected $privateCert;

    /**
     * @var string
     */
    protected $publicCert;

    /**
     * Constructor
     *
     * @param string|array $privateCert
     * @param string|array $publicCert
     */
    public function __construct($privateCert, $publicCert = '')
    {
        if (is_array($privateCert)) {
            $this->privateCert = $privateCert['private'];
            $this->publicCert  = $privateCert['public'];
        } else {
            $this->privateCert = $privateCert;
            $this->publicCert  = $publicCert;
        }
    }

    /**
     * @param string $data
     *
     * @return array
     * @throws Exception
     */
    public function encrypt(string $data)
    {
        if (!is_readable($this->publicCert)) {
            throw new Exception('Public key is not readable.');
        }

        $cert    = file_get_contents($this->publicCert);
        $pk1     = openssl_get_publickey($cert);
        $iv      = openssl_random_pseudo_bytes(32);
        $success = @openssl_seal($data, $sealed, $keys, [$pk1], 'AES256', $iv);
        openssl_free_key($pk1);

        if (!$success) {
            throw new Exception('encrypt: ' . openssl_error_string());
        }

        return [$sealed, $keys[0], $iv, pathinfo($this->publicCert, PATHINFO_BASENAME)];
    }

    /**
     * @param string $sealed
     * @param string $key
     * @param string $iv
     *
     * @return string
     * @throws Exception
     */
    public function decrypt(string $sealed, string $key, string $iv)
    {
        if (!is_readable($this->privateCert)) {
            throw new Exception('Private key is not readable.');
        }

        $cert    = file_get_contents($this->privateCert);
        $pk1     = openssl_get_privatekey($cert);
        $success = @openssl_open($sealed, $open, $key, $pk1, 'AES256', $iv);
        openssl_free_key($pk1);

        if (!$success) {
            throw new Exception('decrypt: ' . openssl_error_string());
        }

        return $open;
    }
}
