<?php
namespace Service;

use BlocksEdit\Config\Config;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Files;
use BlocksEdit\IO\Paths;
use Entity\User;
use Exception;
use Minishlink\WebPush\Subscription;
use Psr\Log\LoggerInterface;

/**
 * Class WebPush
 */
class WebPush
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Files
     */
    protected $files;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $publicKey = '';

    /**
     * @var string
     */
    protected $privateKey = '';

    /**
     * Constructor
     *
     * @param Config          $config
     * @param Files           $files
     * @param LoggerInterface $logger
     *
     * @throws IOException
     */
    public function __construct(Config $config, Files $files, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->files  = $files;
        $this->logger = $logger;

        $this->publicKey  = $files->read(Paths::combine($config->dirs['config'], 'certs/vapid.pub'));
        $this->privateKey = $files->read(Paths::combine($config->dirs['config'], 'certs/vapid.priv'));
    }

    /**
     * @param User   $user
     * @param string $body
     * @param string $url
     * @param string $icon
     *
     * @return bool
     */
    public function sendOne(User $user, string $body, string $url = '', string $icon = '/assets/images/appicon.png'): bool
    {
        if (!$user->isNotificationsEnabled()) {
            return false;
        }
        $sub = $user->getWebPushSubscription();
        if (!$sub) {
            return false;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => 'https://blocksedit.com',
                    'publicKey'  => $this->publicKey,
                    'privateKey' => $this->privateKey,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth);
            $report  = $webPush->sendOneNotification(
                Subscription::create($sub),
                json_encode([
                    'body' => $body,
                    'icon' => $icon,
                    'url'  => $url
                ])
            );

            return $report->isSuccess();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
