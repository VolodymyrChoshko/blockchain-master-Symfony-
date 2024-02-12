<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class FileSystemExtension
 */
class FileSystemExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('file_exists', 'file_exists')
        ];
    }
}
