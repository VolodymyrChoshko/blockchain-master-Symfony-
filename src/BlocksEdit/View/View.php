<?php
namespace BlocksEdit\View;

use BlocksEdit\Html\FlasherInterface;
use BlocksEdit\Html\NonceGenerator;
use BlocksEdit\Html\NonceGeneratorInterface;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\RouteGenerator;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Http\SessionInterface;
use BlocksEdit\Config\Config;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class View
 *
 * @property Config  config
 * @property string  avatarsUri
 * @property string  assetsUri
 * @property Request request
 * @property string  assetsVersion
 */
class View
{
    /**
     * @var array
     */
    protected static $__globals = [];

    /**
     * @var string
     */
    protected static $__viewsDir;

    /**
     * @var RouteGeneratorInterface
     */
    protected static $__routeGenerator;

    /**
     * @var NonceGeneratorInterface
     */
    protected static $__nonceGenerator;

    /**
     * @var string
     */
    protected $__path;

    /**
     * @var array
     */
    protected $__variables;

    /**
     * @var string
     */
    protected $__layout;

    /**
     * @param Request            $request
     * @param ContainerInterface $container
     */
    public static function registerGlobals(Request $request, ContainerInterface $container)
    {
        $config = $container->get(Config::class);
        self::setGlobal('request', $request);
        self::setRouteGenerator($container->get(RouteGeneratorInterface::class));
        self::setNonceGenerator($container->get(NonceGeneratorInterface::class));
        self::setViewsDir($config->dirs['views']);
        self::setGlobals([
            'uri'           => $config->uri,
            'avatarsUri'    => $config->uris['avatars'],
            'assetsUri'     => $config->uris['assets'],
            'assetsVersion' => $config->env === 'prod' ? $config->assetsVersion : rand(0, 10000),
            'config'        => $config,
            'session'       => $container->get(SessionInterface::class),
            'flasher'       => $container->get(FlasherInterface::class),
            'assets'        => ['js' => [], 'css' => []],
        ]);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public static function setGlobal($key, $value)
    {
        self::$__globals[$key] = $value;
    }

    /**
     * @param array $globals
     */
    public static function setGlobals(array $globals)
    {
        foreach($globals as $key => $value) {
            self::setGlobal($key, $value);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public static function getGlobals(string $key = '')
    {
        if ($key) {
            if (isset(self::$__globals[$key])) {
                return self::$__globals[$key];
            }
            return null;
        }

        return self::$__globals;
    }

    /**
     * @param string $views_dir
     */
    public static function setViewsDir($views_dir)
    {
        self::$__viewsDir = rtrim($views_dir, '/');
    }

    /**
     * @param RouteGenerator $routeGenerator
     */
    public static function setRouteGenerator(RouteGenerator $routeGenerator)
    {
        self::$__routeGenerator = $routeGenerator;
    }

    /**
     * @return RouteGenerator
     */
    public static function getRouteGenerator()
    {
        return self::$__routeGenerator;
    }

    /**
     * @return NonceGenerator
     */
    public static function getNonceGenerator(): NonceGenerator
    {
        return self::$__nonceGenerator;
    }

    /**
     * @param NonceGenerator $_nonceGenerator
     */
    public static function setNonceGenerator(NonceGenerator $_nonceGenerator): void
    {
        self::$__nonceGenerator = $_nonceGenerator;
    }

    /**
     * Constructor
     *
     * @param string $path
     * @param array $variables
     */
    public function __construct($path, array $variables = [])
    {
        $this->__path      = $path;
        $this->__variables = $variables;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->__layout = $layout;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function escape($str)
    {
        return htmlspecialchars($str);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->__variables[$name])) {
            return null;
        }

        $value = $this->__variables[$name];
        if (is_string($value) && $name !== 'content') {
            $value = $this->escape($value);
        }

        return $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->__variables[$name]);
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function render()
    {
        if ($this->__path[0] === DIRECTORY_SEPARATOR) {
            $__file = $this->__path;
        } else {
            $__file = self::$__viewsDir . DIRECTORY_SEPARATOR . $this->__path;
        }

        if (file_exists($__file)) {
            $this->includeFunctions();

            ob_start();
            /** @noinspection PhpIncludeInspection */
            include($__file);
            $view = ob_get_contents();
            ob_end_clean();

            if ($this->__layout) {
                $__vars            = $this->__variables;
                $__vars['content'] = $view;
                $__vars['data']    = $this;
                $__view            = new View($this->__layout, $__vars);

                return $__view->render();
            }

            return $view;
        }

        throw new RuntimeException('Wrong view... (' . $__file . ')');
    }

    /**
     * @deprecated
     * @return string
     */
    public function __toString()
    {
        trigger_error('View::__toString deprecated.', E_USER_DEPRECATED);
        return '';
    }

    /**
     *
     */
    public function includeFunctions()
    {
        require_once(__DIR__ . '/functions.php');
        setView($this);

        foreach(self::$__globals as $key => $value) {
            if (!isset($this->__variables[$key])) {
                $this->__variables[$key] = $value;
            }
        }
    }

    /**
     * @param string $__template
     * @param array  $__vars
     *
     * @return mixed
     */
    public function includeTemplate($__template, $__vars = [])
    {
        $__file    = self::$__viewsDir . DIRECTORY_SEPARATOR . $__template;
        $__restore = $this->__variables;
        $this->__variables = array_merge($this->__variables, $__vars);

        $ret = include($__file);

        $this->__variables = $__restore;

        return $ret;
    }
}
