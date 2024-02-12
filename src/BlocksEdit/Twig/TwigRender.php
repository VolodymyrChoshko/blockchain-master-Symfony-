<?php
namespace BlocksEdit\Twig;

use BlocksEdit\Config\Config;
use BlocksEdit\View\View;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Class TwigRender
 */
class TwigRender
{
    /**
     * @var FilesystemLoader
     */
    protected $loader;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * Constructor
     *
     * @param Config          $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $options = [];
        if ($config->env === 'prod') {
            $cacheDir         = $config->dirs['cache'] . '/twig';
            $options['cache'] = new TwigFilesystemCache($cacheDir);
            if (!file_exists($cacheDir)) {
                if (!@mkdir($cacheDir)) {
                    unset($options['cache']);
                    $logger->error('Unable to create twig cache directory.');
                }
            }
        } else {
            $options['debug']            = true;
            $options['strict_variables'] = true;
        }

        $this->loader = new FilesystemLoader($config->dirs['views']);
        $this->twig   = new Environment($this->loader, $options);
    }

    /**
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->twig;
    }

    /**
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->twig->addExtension($extension);
    }

    /**
     * @param string $path
     * @param array  $vars
     * @param string $timezone
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $path, array $vars = [], string $timezone = ''): string
    {
        if ($timezone) {
            $this->twig->getExtension(CoreExtension::class)->setTimezone($timezone);
        }

        $globals  = View::getGlobals();
        $vars     = array_merge($globals, $vars);
        $template = $this->twig->load($path);

        return $template->render($vars);
    }
}
