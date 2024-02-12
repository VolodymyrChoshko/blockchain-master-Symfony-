<?php
namespace BlocksEdit\Twig;

/**
 * Interface ExtensionInterface
 */
interface ExtensionInterface extends \Twig\Extension\ExtensionInterface
{
    /**
     * @param array $globals
     *
     * @return mixed
     */
    public function setGlobals(array $globals);
}
