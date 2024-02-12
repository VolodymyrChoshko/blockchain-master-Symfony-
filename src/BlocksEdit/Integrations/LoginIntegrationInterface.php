<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Http\Request;

/**
 * Interface LoginIntegrationInterface
 */
interface LoginIntegrationInterface
{
    /**
     * @return string
     */
    public function getLoginButtonLabel(): string;

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getLoginPath(Request $request): string;
}
