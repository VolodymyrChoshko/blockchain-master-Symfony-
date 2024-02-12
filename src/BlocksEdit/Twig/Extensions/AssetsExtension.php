<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Config\Config;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AssetsExtension
 */
class AssetsExtension extends AbstractExtension
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $manifest = [];

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
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('assetManifest', [$this, 'assetManifest'])
        ];
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function asset(string $path): string
    {
        if (stripos($path, 'http') === 0) {
            return $path;
        }

        if (!$this->manifest) {
            $raw = file_get_contents($this->config->assetsManifest);
            $this->manifest = json_decode($raw, true);
        }
        if (isset($this->manifest[$path])) {
            return $this->manifest[$path];
        }

        $path          = trim($path, '/');
        $assetsVersion = $this->config->env === 'prod' ? $this->config->assetsVersion : rand(0, 10000);
        if (strpos($path, '?') === false) {
            $path .= '?v=' . $assetsVersion;
        } else {
            $path .= '&v=' . $assetsVersion;
        }

        return $this->config->uris['assets'] . "/$path";
    }

    /**
     * @return array
     */
    public function assetManifest(): array
    {
        if (!$this->manifest) {
            $raw = file_get_contents($this->config->assetsManifest);
            $this->manifest = json_decode($raw, true);
        }

        $out = [];
        foreach($this->manifest as $key => $value) {
            if (preg_match('~^build/.*\.js$~', $key)) {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}
