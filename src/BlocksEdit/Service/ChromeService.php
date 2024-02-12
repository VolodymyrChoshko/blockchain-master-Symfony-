<?php
namespace BlocksEdit\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use Throwable;

/**
 * Class ChromeService
 */
class ChromeService implements ChromeServiceInterface
{
    /**
     * @var string
     */
    protected $serviceUrl;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * Constructor
     *
     * Note: Symfony service container cannot auto-wire this without a default
     * value.
     *
     * @param string               $serviceUrl
     * @param ClientInterface|null $client
     */
    public function __construct(string $serviceUrl = '', ClientInterface $client = null)
    {
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->client     = $client ?? new Client();
    }

    /**
     * {@inheritDoc}
     */
    public function setHttpClient(ClientInterface $client): ChromeServiceInterface
    {
        $this->client = $client;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function pdf(string $html, array $options = [], $screenshot = true): string
    {
        if ($screenshot) {
            $options['fullPage'] = true;
        }

        $path = $screenshot ? 'screenshot/pdf' : 'pdf';

        return $this->doRequest($path, [
            'html'    => $html,
            'options' => $options
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function pdfURL(string $url, array $options = [], $screenshot = true): string
    {
        if (isset($options['mobile']) && !$options['mobile']) {
            return $this->doRequest('pdf', [
                'url'     => $url,
                'options' => $options
            ]);
        }

        return $this->doRequest('screenshot/pdf', [
            'url'     => $url,
            'options' => $options
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function screenshot(string $html, array $options = []): string
    {
        return $this->doRequest('screenshot', [
            'html'    => $html,
            'options' => $options
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function screenshotURL(string $url, array $options = []): string
    {
        return $this->doRequest('screenshot', [
            'url'     => $url,
            'options' => $options
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function scrape(string $html, array $options = []): array
    {
        $body = $this->doRequest('scrape', [
            'html'    => $html,
            'options' => $options
        ]);

        return json_decode($body, true);
    }

    /**
     * {@inheritDoc}
     * @throws Throwable
     */
    public function scrapeAsync(AsyncRequests $asyncRequests): array
    {
        $promises = [];
        $client   = new Client();
        foreach($asyncRequests->getRequests() as $request) {
            $json = [
                'options' => $request['options']
            ];
            if (!empty($request['html'])) {
                $json['html'] = $request['html'];
            } else {
                $json['url'] = $request['url'];
            }
            $promises[] = $client->postAsync(sprintf('%s/scrape', $this->serviceUrl), [
                RequestOptions::JSON => $json
            ]);
        }

        $responses = Utils::unwrap($promises);
        $results   = [];
        foreach($responses as $response) {
            $results[] = json_decode((string)$response->getBody(), true);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function scrapeURL(string $url, array $options = []): string
    {
        $body = $this->doRequest('scrape', [
            'url'     => $url,
            'options' => $options
        ]);

        return json_decode($body, true);
    }

    /**
     * @param string $path
     * @param array  $options
     *
     * @return string
     * @throws Exception
     */
    protected function doRequest(string $path, array $options): string
    {
        try {
            $response = $this->client->request('POST', sprintf('%s/%s', $this->serviceUrl, $path), [
                RequestOptions::JSON => $options
            ]);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        return (string)$response->getBody();
    }
}
