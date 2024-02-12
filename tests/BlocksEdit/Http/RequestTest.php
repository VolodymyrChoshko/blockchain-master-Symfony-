<?php
namespace Tests\BlocksEdit\Http;

use BlocksEdit\Http\Request;
use BlocksEdit\Http\Route;
use BlocksEdit\Http\SessionInterface;
use BlocksEdit\Test\TestCase;
use Exception;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @coversDefaultClass \BlocksEdit\Http\Request
 */
class RequestTest extends TestCase
{
    const HTTP_HOST   = '176.app.blocksedit.com';
    const REQUEST_URI = '/testing?foo==bar';

    /**
     * @var SessionInterface|Stub
     */
    public $session;

    /**
     *
     */
    public function setUp(): void
    {
        $this->session = $this->createStub(SessionInterface::class);
    }

    /**
     * @param array      $options
     * @param Route|null $route
     *
     * @return Request
     */
    public function getRequest(array $options = [], Route $route = null)
    {
        $get     = $options['get'] ?? [];
        $post    = $options['post'] ?? [];
        $files   = $options['files'] ?? [];
        $cookies = $options['cookies'] ?? [];
        $server  = array_merge([
            'HTTPS'                 => 'on',
            'HTTP_HOST'             => self::HTTP_HOST,
            'REQUEST_URI'           => self::REQUEST_URI,
            'REQUEST_METHOD'        => 'GET',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_CONTENT_TYPE'     => 'application/json'
        ], $options['server'] ?? []);

        $request = new Request(
            $this->session,
            $server,
            $get,
            $post,
            $files,
            $cookies
        );

        if (!$route) {
            $match = [
                'match'       => '/{oid<\d+>?0}',
                'class'       => 'Controller\Dashboard\IndexController',
                'method'      => 'indexAction',
                'methods'     => ['GET', 'POST'],
                'grants'      => ['USER'],
                'integration' => null
            ];
            $route = new Route('testing_route', $match, [], []);
        }
        $request->setRoute($route);

        return $request;
    }

    /**
     * @covers ::getUri
     * @throws Exception
     */
    public function testGetUri()
    {
        $actual = $this->getRequest()->getUri();
        $this->assertEquals('https://'. self::HTTP_HOST . '/testing', $actual);
    }

    /**
     * @covers ::getPath
     * @throws Exception
     */
    public function testGetPath()
    {
        $actual = $this->getRequest()->getPath();
        $this->assertEquals('/testing', $actual);
    }

    /**
     * @covers ::getHost
     * @throws Exception
     */
    public function testGetHost()
    {
        $actual = $this->getRequest()->getHost();
        $this->assertEquals(self::HTTP_HOST, $actual);
    }

    /**
     * @covers ::isHttps
     * @throws Exception
     */
    public function testIsHttps()
    {
        $this->assertTrue($this->getRequest()->isHttps());
    }

    /**
     * @covers ::getOrgSubdomain
     * @throws Exception
     */
    public function testGetOrgSubdomain()
    {
        $request = $this->getRequest();
        $actual  = $request->getOrgSubdomain();
        $this->assertEquals(176, $actual);
        $this->assertEquals(176, $request->oid);

        $request = $this->getRequest();
        $request->setServer([
            'HTTPS'       => 'on',
            'HTTP_HOST'   => 'app.blocksedit.com',
            'REQUEST_URI' => self::REQUEST_URI
        ]);
        $actual = $request->getOrgSubdomain();
        $this->assertEquals(0, $actual);
    }

    /**
     * @covers ::getDomainForOrg
     * @throws Exception
     */
    public function testGetDomainForOrg()
    {
        $request = $this->getRequest();
        $actual  = $request->getDomainForOrg(176);
        $this->assertEquals('https://176.app.blocksedit.com', $actual);
    }

    /**
     * @covers ::getRootDomain
     * @throws Exception
     */
    public function testGetRootDomain()
    {
        $request = $this->getRequest();
        $actual  = $request->getRootDomain();
        $this->assertEquals('app.blocksedit.com', $actual);
    }

    /**
     * @covers ::getMethod
     * @throws Exception
     */
    public function testGetMethod()
    {
        $request = $this->getRequest();
        $actual  = $request->getMethod();
        $this->assertEquals('GET', $actual);
    }

    /**
     * @covers ::isPost
     * @throws Exception
     */
    public function testIsPost()
    {
        $this->assertFalse($this->getRequest()->isPost());
    }

    /**
     * @covers ::isAjax
     * @throws Exception
     */
    public function testIsAjax()
    {
        $this->assertTrue($this->getRequest()->isAjax());
    }

    /**
     * @covers ::isJson
     * @throws Exception
     */
    public function testIsJson()
    {
        $this->assertTrue($this->getRequest()->isJson());
    }
}
