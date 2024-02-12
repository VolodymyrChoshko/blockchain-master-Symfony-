<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\Invitation;
use Exception;

/**
 * Class InvitationsRepository
 */
class InvitationsRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return null|Invitation
     * @throws Exception
     */
    public function findByID(int $id): ?Invitation
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param string $token
     *
     * @return null|Invitation
     * @throws Exception
     */
    public function findByToken(string $token): ?Invitation
    {
        return $this->findOne([
            'token' => $token
        ]);
    }

    /**
     * @param string $email
     * @param int    $tid
     *
     * @return null|Invitation
     * @throws Exception
     */
    public function findByEmailAndTemplate(string $email, int $tid): ?Invitation
    {
        return $this->findOne([
            'email'  => $email,
            'tmpId' => $tid
        ]);
    }

    /**
     * @param string $email
     * @param int    $oid
     *
     * @return null|Invitation
     * @throws Exception
     */
    public function findByEmailAndOrganization(string $email, int $oid): ?Invitation
    {
        return $this->findOne([
            'email'  => $email,
            'orgId' => $oid
        ]);
    }

    /**
     * @param string $email
     * @param int    $oid
     *
     * @return Invitation[]
     * @throws Exception
     */
    public function findAllByEmailAndOrganization(string $email, int $oid): array
    {
        return $this->find([
            'email' => $email,
            'orgId' => $oid
        ]);
    }

    /**
     * @param int $tid
     *
     * @return Invitation[]
     * @throws Exception
     */
    public function findByTemplate(int $tid): array
    {
        return $this->find([
            'tmpId'      => $tid,
            'isAccepted' => 0
        ]);
    }

    /**
     * @param object|Invitation $entity
     *
     * @throws Exception
     */
    public function insert(object $entity)
    {
        if (!$entity->getToken()) {
            $entity->setToken($this->generateToken());
        }

        parent::insert($entity);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generateToken(): string
    {
        do {
            $token = md5(mt_rand() . uniqid());
            $found = $this->findByToken($token);
        } while($found);

        return $token;
    }
}
