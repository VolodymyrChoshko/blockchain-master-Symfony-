<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class DumpExtension
 */
class DumpExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump', [$this, 'dump'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param mixed $value
     */
    public function dump($value)
    {
        dump($value);
    }
}
