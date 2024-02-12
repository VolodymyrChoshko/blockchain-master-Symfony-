<?php
namespace BlocksEdit\IO;

use BlocksEdit\System\Required;

/**
 *
 */
trait FilesTrait
{
    /**
     * @var Files
     */
    protected $files;

    /**
     * @Required
     * @param Files $files
     */
    public function setFiles(Files $files)
    {
        $this->files = $files;
    }
}
