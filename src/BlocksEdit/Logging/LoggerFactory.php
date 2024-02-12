<?php
namespace BlocksEdit\Logging;

use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\Request;
use BlocksEdit\Config\Config;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use Psr\Log\NullLogger;
use Repository\UserRepository;

/**
 * Class LoggerFactory
 */
class LoggerFactory
{
    /**
     * @param \BlocksEdit\Config\Config $config
     * @param PDO                       $pdo
     * @param Request                   $request
     * @param MailerInterface           $mailer
     * @param UserRepository            $userRepository
     *
     * @return Logger|NullLogger
     */
    public static function create(
        Config $config,
        PDO $pdo,
        Request $request,
        MailerInterface $mailer,
        UserRepository $userRepository
    )
    {
        return self::createForEnv($config, $config->env, $pdo, $request, $mailer, $userRepository);
    }

    /**
     * @param Config          $config
     * @param string          $env
     * @param PDO             $pdo
     * @param Request         $request
     * @param MailerInterface $mailer
     * @param UserRepository  $userRepository
     *
     * @return Logger|NullLogger
     */
    public static function createForEnv(
        Config $config,
        string $env,
        PDO $pdo,
        Request $request,
        MailerInterface $mailer,
        UserRepository $userRepository
    )
    {
        $logFile = $config->dirs['logs'] . $env . '.log';
        $level   = Logger::DEBUG;
        if ($env === 'prod') {
            $level = Logger::NOTICE;
        }

        try {
            $logger = new Logger('be');
            $logger->pushHandler(new StreamHandler($logFile, $level));
            $logger->pushHandler(new DatabaseLogHandler($pdo, $request, $level));
            $logger->pushHandler(new MailerLogHandler($mailer, $userRepository, Logger::CRITICAL));
        } catch (Exception $e) {
            $logger = new NullLogger();
        }

        return $logger;
    }
}
