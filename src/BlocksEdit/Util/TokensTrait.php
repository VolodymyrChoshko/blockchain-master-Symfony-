<?php
namespace BlocksEdit\Util;

use BlocksEdit\System\Required;

/**
 *
 */
trait TokensTrait
{
    /**
     * @var Tokens
     */
    protected $tokens;

    /**
     * @Required
     * @param Tokens $tokens
     */
    public function setTokens(Tokens $tokens)
    {
        $this->tokens = $tokens;
    }
}
