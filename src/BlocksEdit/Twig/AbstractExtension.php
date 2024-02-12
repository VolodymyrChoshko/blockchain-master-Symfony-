<?php
namespace BlocksEdit\Twig;

/**
 * Class AbstractExtension
 */
abstract class AbstractExtension
    extends \Twig\Extension\AbstractExtension
    implements ExtensionInterface
{
    /**
     * @var array
     */
    protected $globals = [];

    /**
     * {@inheritDoc}
     */
    public function setGlobals(array $globals)
    {
        $this->globals = $globals;
    }
}
