<?php
namespace BlocksEdit\System;

use BlocksEdit\Config\Config;
use Composer\Autoload\ClassLoader;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;

/**
 * Class ClassFinder
 */
class ClassFinder implements ClassFinderInterface
{
    const NS = '\\';

    /**
     * @var ClassLoader
     */
    protected $autoloader;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->autoloader = require($config->dirs['root'] . 'vendor/autoload.php');
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceClasses(string $namespace, bool $includePaths = false, bool $recursive = true): array
    {
        $namespace = $namespace . '\\';
        $prefixes  = $this->autoloader->getPrefixesPsr4();
        if (!isset($prefixes[$namespace])) {
            throw new RuntimeException('Invalid namespace ' . $namespace);
        }

        $found = [];
        if ($recursive) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prefixes[$namespace][0]));
        } else {
            $rii = new DirectoryIterator($prefixes[$namespace][0]);
        }

        foreach($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            $path      = $file->getRealPath();
            $className = $this->getClassNamespaceFromFile($path) . '\\' . $this->getClassNameFromFile($path);
            if ($className !== '\\') {
                if ($includePaths) {
                    $found[$path] = $className;
                } else {
                    $found[] = $className;
                }
            }
        }

        return $found;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassFullNameFromFile($filePathName): string
    {
        return $this->getClassNamespaceFromFile($filePathName) . '\\' . $this->getClassNameFromFile($filePathName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFQClassName(string $referenceClassName, string $className): string
    {
        if ($className[0] === self::NS) {
            return $className;
        }

        $namespace = [];
        $imported  = [];

        $ref    = new ReflectionClass($referenceClassName);
        $src    = file_get_contents($ref->getFileName());
        $tokens = token_get_all($src);
        $len    = count($tokens);
        for ($i = 0; $i < $len; $i++) {
            $token = $tokens[$i];
            if ($token[0] === T_USE) {
                $buffer = [];
                for(; $i < $len; $i++) {
                    if ($tokens[$i] === ';') {
                        break;
                    }
                    if (in_array($tokens[$i][0], [319, 390])) {
                        $buffer[] = $tokens[$i][1];
                    } else if ($tokens[$i][0] === 338) {
                        $buffer[] = ' as ';
                    }
                }

                $imported[] = trim(join('', $buffer), self::NS);
            } else if ($token[0] === T_NAMESPACE) {
                $buffer = [];
                for(; $i < $len; $i++) {
                    if ($tokens[$i] === ';') {
                        break;
                    }
                    if (in_array($tokens[$i][0], [319, 390])) {
                        $buffer[] = $tokens[$i][1];
                    }
                }

                $namespace = trim(join('', $buffer), self::NS);
            }
        }

        $className = trim($className, self::NS);
        $candidate = $this->combine($namespace, $className);
        if (class_exists($candidate)) {
            return $candidate;
        }

        foreach($imported as $import) {
            $parts = explode(' as ', $import, 2);
            if (count($parts) === 1) {
                if ($parts[0] === $className) {
                    return self::NS . $parts[0];
                }
            } else {
                $more  = explode(self::NS, $className);
                $start = array_shift($more);
                if ($start === $parts[1]) {
                    $candidate = $this->combine($parts[0], ...$more);
                    if (class_exists($candidate)) {
                        return $candidate;
                    }
                }

                if ($parts[1] === $className) {
                    return self::NS . $parts[0];
                }
            }

            $candidate = $this->combine($parts[0], $className);
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return self::NS . $className;
    }

    /**
     * @param ... $parts
     *
     * @return string
     */
    protected function combine(...$parts): string
    {
        return self::NS . join(self::NS, $parts);
    }

    /**
     * get the class namespace form file path using token
     *
     * @param string $filePathName
     *
     * @return  null|string
     */
    protected function getClassNamespaceFromFile(string $filePathName): ?string
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (!$namespace_ok) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * get the class name form file path using token
     *
     * @param string $filePathName
     *
     * @return  mixed
     */
    protected function getClassNameFromFile(string $filePathName)
    {
        $php_code = file_get_contents($filePathName);

        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        if (!isset($classes[0])) {
            return '';
        }
        return $classes[0];
    }
}
