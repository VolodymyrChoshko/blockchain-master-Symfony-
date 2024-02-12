<?php
namespace BlocksEdit\Http;

/**
 * Class Request
 */
class Request
{
    /**
     * @var array
     */
    protected $server = [];

    /**
     * @var ParameterBag
     */
    public $query;

    /**
     * @var ParameterBag
     */
    public $post;

    /**
     * @var ParameterBag
     */
    public $json;

    /**
     * @var ParameterBag
     */
    public $request;

    /**
     * @var ParameterBag
     */
    public $files;

    /**
     * @var ParameterBag
     */
    public $headers;

    /**
     * @var ParameterBag
     */
    public $cookies;

    /**
     * @var SessionInterface
     */
    public $session;

    /**
     * @var Route
     */
    public $route;

    /**
     * @var int
     */
    public $oid = 0;

    /**
     * @param SessionInterface $session
     *
     * @return Request
     */
    public static function createFromGlobals(SessionInterface $session): Request
    {
        return new Request($session, $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);
    }

    /**
     * Constructor
     *
     * @param SessionInterface $session
     * @param array            $server
     * @param array            $get
     * @param array            $post
     * @param array            $files
     * @param array            $cookies
     */
    public function __construct(
        SessionInterface $session,
        array $server,
        array $get,
        array $post,
        array $files,
        array $cookies
    )
    {
        $this->server  = $server;
        $this->query   = new ParameterBag($this->processVars($get));
        $this->post    = new ParameterBag($this->processVars($post));
        $this->headers = new ParameterBag($this->processHeaders($server));
        $this->files   = new ParameterBag($files);
        $this->cookies = new ParameterBag($cookies);
        $this->session = $session;

        $json = [];
        if ($this->isJson()) {
            $data = file_get_contents('php://input');
            if ($data) {
                $data = @json_decode($data, true);
                if (is_array($data)) {
                    $json = $data;
                }
            }
        }

        $this->json    = new ParameterBag($json);
        $this->request = new ParameterBag(array_merge($this->post->all(), $json));
        $this->oid     = $this->getOrgSubdomain();
    }

    /**
     * @param array $server
     *
     * @return $this
     */
    public function setServer(array $server): Request
    {
        $this->server = $server;
        $this->oid    = $this->getOrgSubdomain();

        return $this;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function setRoute(Route $route): Request
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @param bool $withQuery
     *
     * @return string
     */
    public function getUri(bool $withQuery = false): string
    {
        $uri = sprintf(
            '%s://%s%s',
            $this->isHttps() ? 'https' : 'http',
            $this->getHost(),
            $this->getPath()
        );

        if ($withQuery) {
            $query = $this->query->all();
            if ($query) {
                $uri .= '?' . http_build_query($query);
            }
        }

        return $uri;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        $parts = explode('?', $this->server['REQUEST_URI'] ?? '');
        return $parts[0];
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? '';
    }

    /**
     * @return bool
     */
    public function isHttps(): bool
    {
        return !empty($this->server['HTTPS']);
    }

    /**
     * @return int
     */
    public function getOrgSubdomain(): int
    {
        if (preg_match('/^([\d]+)\.[\w]+\.blocksedit.com$/', $this->server['HTTP_HOST'] ?? '', $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * @param int $oid
     * @param bool $scheme
     *
     * @return string
     */
    public function getDomainForOrg(int $oid = 0, bool $scheme = true): string
    {
        if (!$oid) {
            $oid = $this->getOrgSubdomain();
        }
        if (preg_match('/^([\d]+)\.([\w]+\.blocksedit.com)$/', $this->server['HTTP_HOST'], $matches)) {
            $domain = $oid . '.' . $matches[2];
        } else {
            $domain = $oid . '.' . $this->server['HTTP_HOST'];
        }

        if (!$scheme) {
            return $domain;
        }

        return 'https://' . $domain;
    }

    /**
     * @return string
     */
    public function getRootDomain(): string
    {
        if (preg_match('/^([\d]+)\.([\w]+\.blocksedit.com)$/', $this->server['HTTP_HOST'], $matches)) {
            return $matches[2];
        }

        return $this->server['HTTP_HOST'];
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->server['REQUEST_METHOD'] === 'POST';
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->headers->get('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        if ($ct = $this->headers->get('Content-Type')) {
            return stripos($ct, 'application/json') !== false;
        }

        return false;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param int    $expires
     */
    public function setCookie(string $name, $value, int $expires = 86400)
    {
        setcookie($name, $value, time() + $expires, '/', '.' . $this->getRootDomain(), true, true);
    }

    /**
     * @param string $name
     */
    public function removeCookie(string $name)
    {
        /** @phpstan-ignore-next-line */
        setcookie($name, null, -1, '/', '.' . $this->getRootDomain());
    }

    /**
     * @param string $response
     * @param string $contentType
     *
     * @return void
     */
    public function finishRequest(string $response, string $contentType = 'application/json')
    {
        ob_start();
        echo $response;
        $size = ob_get_length();
        header("Content-Encoding: none");
        header("Content-Length: $size");
        header("Content-Type: $contentType");
        header("Connection: close");
        ob_end_flush();
        ob_flush();
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Processes the GET and POST vars
     *
     * @param array $vars
     *
     * @return array
     */
    protected function processVars(array $vars): array
    {
        $result = [];
        foreach($vars as $key => $value) {
            if (substr($key, 0, 2) === '__') {
                $hiddenKey = substr($key, 2);
                if (!isset($vars[$hiddenKey])) {
                    $result[$hiddenKey] = $value;
                    continue;
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array $server
     *
     * @return array
     */
    protected function processHeaders(array $server): array
    {
        $headers = [];
        foreach($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
                $name = str_replace('_', ' ', $name);
                $name = ucwords(strtolower($name));
                $name = str_replace(' ', '-', $name);
                $headers[$name] = $value;
            }
        }

        if (!empty($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }
        if (!empty($server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
