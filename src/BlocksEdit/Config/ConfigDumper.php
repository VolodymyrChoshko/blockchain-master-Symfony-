<?php
namespace BlocksEdit\Config;

/**
 * Class ConfigDumper
 */
class ConfigDumper
{
    /**
     * @param Config $config
     *
     * @return string
     */
    public function dump(Config $config): string
    {
        $members = [];
        foreach($config->toArray() as $key => $value) {
            $type = gettype($value);
            if (is_array($value)) {
                $value = var_export($value, true);
            } else if ($type === 'string') {
                $value = "'" . addslashes($value) . "'";
            } else if ($type === 'boolean') {
                $value = $value ? 'true' : 'false';
            }
            $members[] = <<<MEMBER
/** @var ${type} */
public \$${key} = ${value};
MEMBER;
        }

        $members = join("\n", $members);

        return <<<OUT
<?php
// This file was created automatically. Any changes to it will be overwritten.
use BlocksEdit\Config\Config;

class ProjectConfig extends Config
{
${members}

    /** @var string */
    public \$env;
    /** @var string */
    public \$configDir;
    /** @var string */
    public \$projectDir;

    public function __construct(\$env, \$configDir, \$projectDir) {
        \$this->env        = \$env;
        \$this->configDir  = \$configDir;
        \$this->projectDir = \$projectDir;
    }
}
OUT;
    }
}
