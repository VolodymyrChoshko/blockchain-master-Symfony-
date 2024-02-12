<?php
namespace BlocksEdit\Config;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 *
 * @property string   $uri
 * @property string   $assetsVersion
 * @property array    $pdo
 * @property array    $redis
 * @property array    $stripe
 * @property array    $certs
 * @property array    $email
 * @property array    $aws
 * @property array    $dirs
 * @property array    $uris
 * @property array    $cdn
 * @property array    $sqs
 * @property array    $socket
 * @property boolean  $cacheEnabled
 * @property array    $namespaces
 * @property string   $chromeServiceUrl
 * @property array    $integrations
 * @property int      $starterTemplate
 * @property array    $starterEmails
 * @property string   $assetsManifest
 */
class Config
{
    /**
     * @var array
     */
    protected static $required
        = [
            'uri'             => 'string',
            'assetsVersion'   => 'string',
            'integrations'    => 'array',
            'pdo'             => 'array',
            'redis'           => 'array',
            'certs'           => 'array',
            'stripe'          => 'array',
            'dirs'            => 'array',
            'uris'            => 'array',
            'cdn'             => 'array',
            'namespaces'      => 'array',
            'starterTemplate' => 'integer',
            'starterEmails'   => 'array',
            'assetsManifest'  => 'string'
        ];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    public $env;

    /**
     * @var string
     */
    public $configDir;

    /**
     * @var string
     */
    public $projectDir;

    /**
     * Constructor
     *
     * @param string $env
     * @param string $configDir
     * @param string $projectDir
     */
    public function __construct(string $env, string $configDir, string $projectDir)
    {
        $this->env        = $env;
        $this->configDir  = $configDir;
        $this->projectDir = $projectDir;
        $this->load();
    }

    /**
     *
     */
    public function load()
    {
        $config = file_get_contents($this->configDir . '/config.yaml');
        $config = str_replace('%be.projectDir%', $this->projectDir, $config);
        $config = Yaml::parse($config);
        $data   = $config['parameters'];

        $envFile = $this->configDir . "/config-$this->env.yaml";
        if (file_exists($envFile)) {
            $envConfig = Yaml::parseFile($envFile);
            if (isset($envConfig['parameters'])) {
                $envConfig = $envConfig['parameters'];
            }
            $data = array_merge($data, $envConfig);
        }

        $version = Yaml::parseFile($this->configDir . '/version.yaml');
        $data['assetsVersion'] = $version['version'];

        if ($this->env === 'dev') {
            $this->validate($data);
        }
        $this->data = $data;
    }

    /**
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        if (isset( $this->data[$name])) {
            return $this->data[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function set(string $name, $value): Config
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return $this|Config
     */
    public function __set(string $name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    protected function validate(array $data)
    {
        foreach(self::$required as $key => $type) {
            if (!isset($data[$key])) {
                throw new RuntimeException(
                    sprintf('Missing required configuration value for "%s".', $key)
                );
            }

            $dType = gettype($data[$key]);
            if ($dType !== $type) {
                throw new RuntimeException(
                    sprintf('Invalid configuration type %s for key "%s". Expected type %s.', $dType, $key, $type)
                );
            }
        }
    }
}
