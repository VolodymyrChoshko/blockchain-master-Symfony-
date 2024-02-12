<?php
namespace BlocksEdit\Integrations\Filesystem;

/**
 * Class FileInfo
 */
class FileInfo
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $mtime;

    /**
     * @var bool
     */
    protected $isDir;

    /**
     * @var mixed
     */
    protected $meta;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $path
     * @param int    $size
     * @param int    $mtime
     * @param bool   $isDir
     * @param mixed  $meta
     */
    public function __construct($name, $path, $size, $mtime = 0, $isDir = false, $meta = null)
    {
        $this->name  = $name;
        $this->path  = str_replace('\\', '/', $path);
        $this->size  = (int)$size;
        $this->mtime = (int)$mtime;
        $this->isDir = $isDir;
        $this->meta  = $meta;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getPathName()
    {
        if ($this->path === '/') {
            return sprintf('/%s', $this->name);
        }
        return sprintf('%s/%s', $this->path, $this->name);
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getMtime()
    {
        return $this->mtime;
    }

    /**
     * @return bool
     */
    public function isDir()
    {
        return $this->isDir;
    }

    /**
     * @return bool
     */
    public function isDot()
    {
        return $this->isDir() && ($this->name === '.' || $this->name === '..');
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }
}
