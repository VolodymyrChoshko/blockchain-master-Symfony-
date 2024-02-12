<?php
namespace BlocksEdit\Integrations\Services\SalesForce;

use BlocksEdit\Integrations\Exception\InsufficientPrivilegesException;
use BlocksEdit\Integrations\Exception\IntegrationException;
use BlocksEdit\Integrations\Exception\OAuthUnauthorizedException;
use BlocksEdit\Cache\CacheInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Class RestClient
 */
class RestClient
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $guzzle;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var string
     */
    protected $baseURL;

    /**
     * @var string
     */
    protected $baseAuthURL;

    /**
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $localCache = [];

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param array          $settings
     * @param float          $timeout
     */
    public function __construct(CacheInterface $cache, array $settings, float $timeout = 60.0)
    {
        $this->cache       = $cache;
        $this->settings    = $settings;
        $this->baseURL     = rtrim($settings['base_rest_url'], '/');
        $this->baseAuthURL = rtrim($settings['base_auth_url'], '/');
        $this->guzzle      = new Client([
            'timeout' => $timeout,
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    /**
     * @param string $clientID
     * @param string $clientSecret
     * @param int    $accountId
     * @param string $scope
     * @param bool   $fresh
     *
     * @return AccessToken
     * @throws Exception
     */
    public function requestAccessToken(
        string $clientID,
        string $clientSecret,
        int $accountId,
        string $scope,
        bool $fresh = false
    ): AccessToken
    {
        try {
            $cacheKey = sprintf('salesforce_access_token_%s', md5($clientID . $clientSecret . $accountId));
            if (!$fresh) {
                if ($data = $this->cache->get($cacheKey)) {
                    $this->accessToken = new AccessToken($data);

                    return $this->accessToken;
                }
            }

            $body = [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientID,
                'client_secret' => $clientSecret,
                'scope'         => $scope
            ];
            if ($accountId) {
                $body['account_id'] = $accountId;
            }

            $resp = $this->guzzle->post($this->baseAuthURL . '/v2/token', [
                'json' => $body
            ]);

            $json              = $this->jsonDecode($resp);
            $this->accessToken = new AccessToken($json);
            $this->cache->set($cacheKey, $json, $json['expires_in']);

            return $this->accessToken;
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param PagedItems $pagedItems
     *
     * @return PagedItems
     * @throws GuzzleException
     */
    public function nextPage(PagedItems $pagedItems): PagedItems
    {
        $url  = $pagedItems->getURL();
        $json = $this->doGetRequest(
            $url,
            $pagedItems->getPage() + 1,
            $pagedItems->getPageSize()
        );

        return new PagedItems($json, $url);
    }

    /**
     * @param PagedItems $pagedItems
     *
     * @return array
     * @throws GuzzleException
     */
    public function whileHasMore(PagedItems $pagedItems): array
    {
        $items = $pagedItems->getItems();
        if ($pagedItems->hasMore()) {
            do {
                $pagedItems = $this->nextPage($pagedItems);
                $items      = array_merge($items, $pagedItems->getItems());
            } while ($pagedItems->hasMore());
        }

        return $items;
    }

    /**
     * @param int $categoryID
     * @param int $page
     * @param int $pageSize
     *
     * @return PagedItems
     * @throws GuzzleException
     */
    public function fetchAssets(int $categoryID, int $page = 1, int $pageSize = 50): PagedItems
    {
        $url  = sprintf(
            '%s/asset/v1/content/assets?$filter=%s',
            $this->baseURL,
            urlencode("category.id eq $categoryID")
        );
        $json = $this->doGetRequest($url, $page, $pageSize);

        return new PagedItems($json, $url);
    }

    /**
     * @param array $asset
     *
     * @return array
     * @throws GuzzleException
     */
    public function postAsset(array $asset): array
    {
        $url = sprintf('%s/asset/v1/content/assets', $this->baseURL);

        return $this->doPostRequest($url, $asset);
    }

    /**
     * @param array $assets
     *
     * @return array
     * @throws Exception
     */
    public function postAssetsAsync(array $assets): array
    {
        $url = sprintf('%s/asset/v1/content/assets', $this->baseURL);

        $requests = [];
        foreach($assets as $asset) {
            $requests[] = $this->makeRequest('POST', $url, $asset);
        }

        return $this->doRequestAsync($requests);
    }

    /**
     * @param int $id
     *
     * @return mixed
     * @throws GuzzleException
     */
    public function deleteAsset(int $id)
    {
        $url = sprintf('%s/asset/v1/content/assets/%d', $this->baseURL, $id);

        return $this->doDeleteRequest($url);
    }

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return PagedItems
     * @throws GuzzleException
     */
    public function fetchCategories(int $page = 1, int $pageSize = 50): PagedItems
    {
        $url  = $this->baseURL . '/asset/v1/content/categories';
        $json = $this->doGetRequest($url, $page, $pageSize);

        return new PagedItems($json, $url);
    }

    /**
     * @param string $name
     * @param int    $parentId
     *
     * @return array
     * @throws GuzzleException
     */
    public function postCategory(string $name, int $parentId): array
    {
        $url = sprintf('%s/asset/v1/content/categories', $this->baseURL);

        return $this->doPostRequest($url, compact('name', 'parentId'));
    }

    /**
     * @param int   $id
     * @param array $category
     *
     * @return array
     * @throws GuzzleException
     */
    public function putCategory(int $id, array $category): array
    {
        $url = sprintf('%s/asset/v1/content/categories/%d', $this->baseURL, $id);

        return $this->doPutRequest($url, $category);
    }

    /**
     * @param int $id
     *
     * @return mixed
     * @throws GuzzleException
     */
    public function deleteCategory(int $id)
    {
        $url  = sprintf('%s/asset/v1/content/categories/%d', $this->baseURL, $id);

        return $this->doDeleteRequest($url);
    }

    /**
     *
     */
    public function clearLocalCache()
    {
        $this->localCache = [];
    }

    /**
     * @param string $url
     * @param int    $page
     * @param int    $pageSize
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function doGetRequest(string $url, int $page = 1, int $pageSize = 50): array
    {
        $url = sprintf(
            '%s%s$page=%d&$pageSize=%d',
            $url,
            (strpos($url, '?') === false ? '?' : '&'),
            $page,
            $pageSize
        );

        return $this->doRequest('get', $url);
    }

    /**
     * @param string $url
     * @param array  $body
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function doPostRequest(string $url, array $body): array
    {
        return $this->doRequest('post', $url, $body);
    }

    /**
     * @param string $url
     * @param array  $body
     *
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    private function doPutRequest(string $url, array $body): array
    {
        return $this->doRequest('put', $url, $body);
    }

    /**
     * @param string $url
     *
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    private function doDeleteRequest(string $url)
    {
        return $this->doRequest('delete', $url, []);
    }

    /**
     * @param string $method
     * @param string $url
     * @param mixed  $body
     *
     * @return mixed
     * @throws GuzzleException
     * @throws IntegrationException
     */
    private function doRequest(string $method, string $url, $body = null)
    {
        if ($method === 'get' && !empty($this->localCache[$url])) {
            return $this->localCache[$url];
        }

        $options = [
            'http_errors' => false,
        ];

        $req  = $this->makeRequest($method, $url, $body);
        $resp = $this->guzzle->send($req, $options);
        $json = $this->jsonDecode($resp);
        $code = $resp->getStatusCode();

        if ($code > 299) {
            switch ($code) {
                case 401:
                    throw new OAuthUnauthorizedException('You are not authorized to access the selected resource.', 401);
                case 403:
                    throw new InsufficientPrivilegesException($json['message'], 403);
                default:
                    throw new IntegrationException($json['message'], $code);
            }
        }

        if ($method === 'get') {
            $this->localCache[$url] = $json;
        }

        return $json;
    }

    /**
     * @param array $requests
     *
     * @return array
     * @throws Exception
     */
    private function doRequestAsync(array $requests): array
    {
        $options = [
            'http_errors' => false,
        ];

        $promises = [];
        foreach($requests as $request) {
            $promises[] = $this->guzzle->sendAsync($request, $options);
        }
        $responses = Promise\Utils::settle($promises)->wait();

        $results = [];
        foreach($responses as $response) {
            if ($response['state'] !== 'fulfilled') {
                throw new Exception('Got unfulfilled request.');
            }
            $code = $response['value']->getStatusCode();
            $json = $this->jsonDecode($response['value']);
            if ($code > 299) {
                switch ($code) {
                    case 401:
                        throw new OAuthUnauthorizedException('You are not authorized to access the selected resource.', 401);
                    case 403:
                        throw new InsufficientPrivilegesException($json['message'], 403);
                    default:
                        throw new IntegrationException($json['message'], $code);
                }
            }

            $results[] = $json;
        }

        return $results;
    }

    /**
     * @param string $method
     * @param string $url
     * @param mixed  $body
     *
     * @return Request
     */
    private function makeRequest(string $method, string $url, $body = null): Request
    {
        $headers = $this->getRequestHeaders();
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';

            return new Request($method, $url, $headers, json_encode($body));
        }

        return new Request($method, $url, $headers);
    }

    /**
     * @return array
     */
    private function getRequestHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken->getToken()
        ];
    }

    /**
     * @param ResponseInterface $resp
     *
     * @return mixed
     */
    private function jsonDecode(ResponseInterface $resp)
    {
        return json_decode((string)$resp->getBody(), true);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return sprintf(
            '%s_%s_%d',
            md5($key),
            md5($this->settings['client_id'] . $this->settings['client_secret']),
            $this->settings['account_id']
        );
    }
}
