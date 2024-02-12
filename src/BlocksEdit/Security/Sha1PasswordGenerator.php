<?php
namespace BlocksEdit\Security;

/**
 * Class Sha1PasswordGenerator
 */
class Sha1PasswordGenerator implements PasswordGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function generate(string $plainText): string
    {
        return sha1(md5($plainText));
    }

    /**
     * @inheritDoc
     */
    public function isMatch(string $plainText, string $password): bool
    {
        return $password === $this->generate($plainText);
    }
}
