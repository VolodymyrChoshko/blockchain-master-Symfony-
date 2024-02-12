<?php
namespace BlocksEdit\Config;

use ProjectConfig;

/**
 * Class ConfigFactory
 */
class ConfigFactory
{
    /**
     * @param string $env
     * @param string $cacheDir
     * @param string $configDir
     * @param string $projectDir
     *
     * @phpstan-ignore-next-line
     * @return Config|ProjectConfig
     */
    public function create(string $env, string $cacheDir, string $configDir, string $projectDir)
    {
        $configFile = $cacheDir . '/config.php';
        if (!file_exists($configFile)) {
            $config = new Config($env, $configDir, $projectDir);
            $dumper = new ConfigDumper();
            file_put_contents($configFile, $dumper->dump($config));
            @chmod($configFile, 0777);
        }

        require($configFile);

        /** @phpstan-ignore-next-line */
        return new ProjectConfig($env, $configDir, $projectDir);
    }
}
