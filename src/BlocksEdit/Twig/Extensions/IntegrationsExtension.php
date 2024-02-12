<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class IntegrationsExtension
 */
class IntegrationsExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('canEnableIntegration', [$this, 'canEnableIntegration']),
            new TwigFunction('isFileSystemIntegration', [$this, 'isFileSystemIntegration'])
        ];
    }

    /**
     * @param IntegrationInterface $integration
     * @param array                $sources
     *
     * @return bool
     */
    public function canEnableIntegration(IntegrationInterface $integration, array $sources): bool
    {
        if (!$integration->hasRestriction(IntegrationInterface::ONE_PER_ORG)) {
            return true;
        }
        foreach($sources as $source) {
            if ($source->getClass() === get_class($integration)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param IntegrationInterface $integration
     *
     * @return bool
     */
    public function isFileSystemIntegration(IntegrationInterface $integration): bool
    {
        return $integration instanceof FilesystemIntegrationInterface;
    }
}
