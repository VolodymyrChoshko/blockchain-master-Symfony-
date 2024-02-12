<?php
namespace BlocksEdit\Integrations;

/**
 * Interface RoutableIntegrationInterface
 */
interface RoutableIntegrationInterface
{
    /**
     * @return array
     */
    public function getRoutes(): array;
}
