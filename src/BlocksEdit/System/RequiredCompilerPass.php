<?php
namespace BlocksEdit\System;

use BlocksEdit\Config\Config;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Does the same thing as symfony's @require annotation, which for some reason
 * isn't working.
 */
class RequiredCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    protected $namespaces = [];

    /**
     * Constructor
     *
     * @param array $namespaces
     */
    public function __construct(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        $reader = new AnnotationReader();

        $config      = $container->get(Config::class);
        $classFinder = new ClassFinder($config);
        foreach($this->namespaces as $namespaceName) {
            $classes = $classFinder->getNamespaceClasses($namespaceName);
            foreach($classes as $className) {
                if (!class_exists($className)) {
                    continue;
                }

                $reflectionClass = new ReflectionClass($className);
                foreach($reflectionClass->getMethods() as $method) {
                    $required = $reader->getMethodAnnotation($method, Required::class);
                    if ($required) {
                        if (!$container->has($className)) {
                            $classDef = $container->register($className, $className);
                            $classDef->setPublic(true);
                            $classDef->setAutowired(true);
                        } else {
                            $classDef = $container->getDefinition($className);
                        }
                        $classDef->addMethodCall($method->getName());
                    }
                }
            }
        }
    }
}
