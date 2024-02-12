<?php
use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\Router;
use BlocksEdit\Http\SessionInterface;
use BlocksEdit\System\ContainerFactory;
use BlocksEdit\Config\Config;
use BlocksEdit\Twig\TwigRender;
use BlocksEdit\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

if (empty($_SERVER['APP_ENV'])) {
    die('APP_ENV is not defined.');
}
if (!is_writable(__DIR__ . '/../var/cache')) {
    die('Cache directory is not writable.');
}
if (!is_writable(__DIR__ . '/../var/logs')) {
    die('Logs directory is not writable.');
}

require(__DIR__ . '/../vendor/autoload.php');

umask(0000);
if ($_SERVER['APP_ENV'] !== 'prod') {
    Debug::enable();
} else {
    $handler = ErrorHandler::register();
    /*$handler->setExceptionHandler(function() {
        try {
            $container = require(__DIR__ . '/../src/container.php');
            $twig      = $container->get(TwigRender::class);
            $html      = $twig->render('errors/status.html.twig', [
                'code'    => 500,
                'message' => 'Internal Server Error'
            ]);
            $response = new Response($html, 500);
            $response->send();
        } catch (Exception $e) {
            $msg = (string)$e->getCode();
            die($msg);
        }
    });*/
}

/** @var ContainerInterface $container */
$container = ContainerFactory::instance($_SERVER['APP_ENV']);
$config    = $container->get(Config::class);
$session   = $container->get(SessionInterface::class);
$request   = $container->get(Request::class);
View::registerGlobals($request, $container);
if (isset($handler)) {
    $handler->setDefaultLogger($container->get(LoggerInterface::class));
}

try {
    date_default_timezone_set('America/New_York');
    $container->get(\PDO::class)->exec("SET time_zone = 'America/New_York'");

    $session->setRootDomain($request->getRootDomain())->start();
    $response = $container->get(Router::class)->dispatch($container, $request);
    $response->send();
} catch (StatusCodeException $e) {
    try {
        $twig = $container->get(TwigRender::class);
        $html = $twig->render('errors/status.html.twig', [
            'code'    => $e->getCode(),
            'message' => $e->getMessage()
        ]);
        $response = new Response($html, $e->getCode());
        $response->send();
    } catch (Exception $e) {
        $msg = (string)$e->getCode();
        die($msg);
    }
}
