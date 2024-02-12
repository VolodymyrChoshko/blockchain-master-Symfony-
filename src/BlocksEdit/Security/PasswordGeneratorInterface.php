<?php
namespace BlocksEdit\Security;

/**
 *
 */
interface PasswordGeneratorInterface
{
    /**
     * @param string $plainText
     *
     * @return string
     */
    public function generate(string $plainText): string;

    /**
     * @param string $plainText
     * @param string $password
     *
     * @return bool
     */
    public function isMatch(string $plainText, string $password): bool;
}
