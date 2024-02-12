<?php
namespace BlocksEdit\IO;

use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Exception\SecurityException;
use BlocksEdit\Config\Config;
use InvalidArgumentException;

/**
 * Class IOBase
 */
abstract class IOBase
{
    const PERMISSIONS = 0755;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $modifiableDirs = [];

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $modifiableDirs
     *
     * @return $this
     */
    public function setModifiableDirs(array $modifiableDirs): IOBase
    {
        $this->modifiableDirs = [];
        foreach($modifiableDirs as $dirName) {
            if ($dirName[0] !== '/') {
                $dirName = rtrim($this->config->dirs[$dirName], '/') . '/';
            }

            if (is_link($dirName)) {
                $dirName = readlink($dirName);
            }
            $this->modifiableDirs[] = $dirName;
        }

        usort($this->modifiableDirs, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $this;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getCanonicalPath(string $filename): string
    {
        $path = [];
        foreach(explode('/', $filename) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part !== '..') {
                $path[] = $part;
            } else if (count($path) > 0) {
                array_pop($path);
            } else {
                return '';
            }
        }

        return DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param string $target
     *
     * @return bool
     */
    public function isModifiable(string $target): bool
    {
        $target = $this->getCanonicalPath($target);
        $target = $this->resolveSymlink($target);
        $target = rtrim($target, '/') . '/';
        foreach($this->modifiableDirs as $dir) {
            if (strpos($target, $dir) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $target
     *
     * @return void
     * @throws IOException
     * @throws SecurityException
     */
    protected function verifyModifiable(string $target)
    {
        if (!$this->isModifiable($target)) {
            throw new SecurityException(
                sprintf('Cannot modify file "%s".', $target)
            );
        }
    }

    /**
     * @param string|string[]|FilePathInterface|FilePathInterface[] $filenames
     *
     * @return array
     */
    protected function getPathsFromArgs($filenames): array
    {
        $paths = [];
        if (!is_array($filenames)) {
            $filenames = [$filenames];
        }
        foreach($filenames as $value) {
            if ($value instanceof FilePathInterface) {
                $value = $value->getFilePath();
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf('Values passed to %s must be strings or FilePathInterface.', __CLASS__)
                );
            }
            $paths[] = $value;
        }

        return $paths;
    }

    /**
     * @param string $target
     *
     * @return string
     */
    protected function resolveSymlink(string $target): string
    {
        if (is_link($target)) {
            return readlink($target);
        }

        $parts = explode('/', $target);
        $path  = '';
        while(!empty($parts)) {
            $path .= '/'. array_shift($parts);
            if (is_link($path)) {
                $link = readlink($path);
                if (!empty($parts)) {
                    return $link . '/' . join('/', $parts);
                }
                return $link;
            }

        }

        return $target;
    }
}
