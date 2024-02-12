<?php
namespace Middleware;

use BlocksEdit\Analytics\PageViews;
use BlocksEdit\Http\Middleware;
use BlocksEdit\Http\Request;
use Exception;

/**
 * Class AnalyticsMiddleware
 */
class AnalyticsMiddleware extends Middleware
{
    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * @param Request $request
     */
    public function request(Request $request)
    {
        try {
            if (!$request->isAjax() && stripos($request->headers->get('User-Agent'), 'ELB-HealthChecker') === false) {
                $pageViews = $this->container->get(PageViews::class);
                $pageViews->incrementPageView();
            }
        } catch (Exception $e) {}
    }
}
