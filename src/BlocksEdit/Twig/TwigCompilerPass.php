<?php
namespace BlocksEdit\Twig;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigCompilerPass
 */
class TwigCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $extensions = $container->findTaggedServiceIds('twig.extension');
        if ($extensions) {
            $twigDef = $container->findDefinition('BlocksEdit\Twig\TwigRender');
            foreach($extensions as $key => $value) {
                $argDef = $container->getDefinition($key);
                $twigDef->addMethodCall('addExtension', [$argDef]);
            }
        }
    }
}
