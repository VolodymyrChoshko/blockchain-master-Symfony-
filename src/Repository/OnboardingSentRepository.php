<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\OnboardingSent;
use Exception;

/**
 * Class OnboardingSentRepository
 */
class OnboardingSentRepository extends Repository
{
    /**
     * @param string $email
     * @param string $view
     *
     * @return object|OnboardingSent
     * @throws Exception
     */
    public function findByEmailAndView(string $email, string $view)
    {
        return $this->findOne([
            'email' => $email,
            'view'  => $view
        ]);
    }

    /**
     * @param string $email
     * @param string $view
     *
     * @return bool
     * @throws Exception
     */
    public function isSent(string $email, string $view): bool
    {
        return (bool)$this->findByEmailAndView($email, $view);
    }
}
