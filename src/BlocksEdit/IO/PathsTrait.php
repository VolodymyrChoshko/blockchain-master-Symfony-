<?php
namespace BlocksEdit\IO;

use BlocksEdit\System\Required;

/**
 *
 */
trait PathsTrait
{
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @Required
     * @param Paths $paths
     */
    public function setPaths(Paths $paths)
    {
        $this->paths = $paths;
    }
}
