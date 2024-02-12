<?php
namespace BlocksEdit\Twig;

use RuntimeException;
use Twig\Cache\FilesystemCache;

/**
 * Class TwigFilesystemCache
 */
class TwigFilesystemCache extends FilesystemCache
{
    /**
     * @var int
     */
    protected $twigOptions = 0;

    /**
     * Constructor
     *
     * @param string $directory
     * @param int    $options
     */
    public function __construct(string $directory, int $options = 0)
    {
        parent::__construct($directory, $options);
        $this->twigOptions = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $key, string $content): void
    {
        $dir = dirname($key);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                clearstatcache(true, $dir);
                if (!is_dir($dir)) {
                    throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
                }
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
        }

        $tmpFile = tempnam($dir, basename($key));
        if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $key)) {
            @chmod($key, 0777);

            if (self::FORCE_BYTECODE_INVALIDATION == ($this->twigOptions & self::FORCE_BYTECODE_INVALIDATION)) {
                // Compile cached file into bytecode cache
                if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
                    @opcache_invalidate($key, true);
                } elseif (function_exists('apc_compile_file')) {
                    apc_compile_file($key);
                }
            }

            return;
        }

        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $key));
    }
}
