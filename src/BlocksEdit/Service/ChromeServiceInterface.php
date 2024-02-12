<?php
namespace BlocksEdit\Service;

use Exception;
use GuzzleHttp\ClientInterface;

/**
 * Class ChromeService
 */
interface ChromeServiceInterface
{
    /**
     * @param ClientInterface $client
     *
     * @return ChromeServiceInterface
     */
    public function setHttpClient(ClientInterface $client): ChromeServiceInterface;

    /**
     * @see https://pptr.dev/#?product=Puppeteer&version=v1.19.0&show=api-pagepdfoptions
     *
     * @param string $html
     * @param array  $options
     * @param bool   $screenshot
     *
     * @return string
     * @throws Exception
     */
    public function pdf(string $html, array $options = [], $screenshot = true): string;

    /**
     * @see https://pptr.dev/#?product=Puppeteer&version=v1.19.0&show=api-pagepdfoptions
     *
     * @param string $url
     * @param array  $options
     * @param bool   $screenshot
     *
     * @return string
     * @throws Exception
     */
    public function pdfURL(string $url, array $options = [], $screenshot = true): string;

    /**
     * @param string $html
     * @param array  $options "width", "height", "selector"
     *
     * @return string
     * @throws Exception
     */
    public function screenshot(string $html, array $options = []): string;

    /**
     * @param string $url
     * @param array  $options "width", "height", "selector"
     *
     * @return string
     * @throws Exception
     */
    public function screenshotURL(string $url, array $options = []): string;

    /**
     * @param string $html
     * @param array  $options "width", "height", "selector", "file"
     *
     * @return array
     * @throws Exception
     */
    public function scrape(string $html, array $options = []): array;

    /**
     * @param AsyncRequests $asyncRequests
     *
     * @return array
     */
    public function scrapeAsync(AsyncRequests $asyncRequests): array;

    /**
     * @param string $url
     * @param array  $options "width", "height", "selector", "file"
     *
     * @return string
     * @throws Exception
     */
    public function scrapeURL(string $url, array $options = []): string;
}
