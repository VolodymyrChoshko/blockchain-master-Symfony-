<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Security\OpenSSL;
use BlocksEdit\System\Required;
use Entity\Credential;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Exception;

/**
 * Class CredentialsRepository
 */
class CredentialsRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Credential|null
     * @throws Exception
     */
    public function findByID(int $id): ?Credential
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param Credential $credential
     *
     * @throws Exception
     */
    public function decrypt(Credential $credential)
    {
        $guzzle = new Client();
        $resp   = $guzzle->post($this->config->uris['certs'], [
            RequestOptions::JSON => [
                'sealed' => base64_encode($credential->getSealed()),
                'key'    => base64_encode($credential->getKey()),
                'iv'     => base64_encode($credential->getIv())
            ]
        ]);

        $credential->setUnsealed((string)$resp->getBody());
    }

    /**
     * @param Credential $credential
     *
     * @throws Exception
     */
    public function encrypt(Credential $credential)
    {
        list($sealed, $key, $iv, $with) = $this->openssl->encrypt($credential->getUnsealed());
        $credential
            ->setSealed($sealed)
            ->setKey($key)
            ->setIv($iv)
            ->setWith($with);
    }

    /**
     * @param Credential $credential
     *
     * @throws Exception
     */
    public function encryptAndInsert(Credential $credential)
    {
        $this->encrypt($credential);
        $this->insert($credential);
    }

    /**
     * @param Credential $credential
     *
     * @return int
     * @throws Exception
     */
    public function encryptAndUpdate(Credential $credential): int
    {
        $this->encrypt($credential);

        return $this->update($credential);
    }

    /**
     * @var OpenSSL
     */
    protected $openssl;

    /**
     * @Required()
     * @param OpenSSL $openssl
     */
    public function setOpenSSL(OpenSSL $openssl)
    {
        $this->openssl = $openssl;
    }
}
