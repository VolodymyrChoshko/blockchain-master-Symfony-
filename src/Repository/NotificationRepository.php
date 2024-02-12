<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\System\Required;
use BlocksEdit\System\Serializer;
use BlocksEdit\Http\RouteGeneratorInterface;
use DateTime;
use Entity\Comment;
use Entity\Notification;
use Entity\User;
use Exception;
use Redis;
use Service\Mentions;
use Service\NotificationsMessageQueue;

/**
 * Class NotificationRepository
 */
class NotificationRepository extends Repository
{
    /**
     * @var string[]
     */
    protected static $pushActions = ['mention', 'reply'];

    /**
     * @param int $id
     *
     * @return Notification|null
     * @throws Exception
     */
    public function findByID(int $id): ?Notification
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param User $user
     * @param int  $limit
     * @param int  $offset
     *
     * @return Notification[]
     * @throws Exception
     */
    public function findByTo(User $user, int $limit = 10, int $offset = 0): array
    {
        return $this->find([
            'to' => $user
        ], $limit, $offset, ['dateCreated' => 'DESC']);
    }

    /**
     * @param Comment $comment
     *
     * @return Notification[]
     * @throws Exception
     */
    public function findByComment(Comment $comment): array
    {
        return $this->find([
            'comment' => $comment,
        ]);
    }

    /**
     * @param object $entity
     *
     * @return void
     * @throws Exception
     */
    public function insert(object $entity)
    {
        /** @var Notification $entity */
        if ($entity->getAction() === 'emoji') {
            /** @var Notification $notification */
            $notification = $this->findOne([
                'to'      => $entity->getTo(),
                'comment' => $entity->getComment(),
                'action'  => 'emoji'
            ]);
            if ($notification) {
                $notification->setDateCreated(new DateTime());
                $this->update($notification);

                return;
            }
        }

        parent::insert($entity);

        try {
            $this->redis->publish('notifications', json_encode($this->serializer->serializeNotification($entity)));
            if (in_array($entity->getAction(), self::$pushActions)) {
                $user = $entity->getTo();
                if ($user->isNotificationsEnabled()) {
                    $this->notificationsMessageQueue->send($entity->getId());
                }

                // if ($user->isEmailsEnabled()) {
                    $dateTime = $entity->getDateCreated()->format('F j, Y h:ia');

                    if ($entity->getAction() === 'reply' && $entity->getComment()->getUser()->isNotificationsEnabled()) {
                        $comment    = $entity->getComment();
                        $from       = $entity->getFrom()->getName();
                        $emailTitle = $comment->getEmail()->getTitle();
                        $emailUrl   = $this->routeGenerator->generate('build_comments_redirect', [
                            'id'  => $entity->getId()
                        ], 'absolute');

                        $this->mentions->updateAll($comment, true);
                        $this->emailSender->sendNotificationReply(
                            $user->getEmail(),
                            $from,
                            str_replace('&nbsp;', ' ', strip_tags($comment->getContent())),
                            $dateTime,
                            $emailTitle,
                            $emailUrl . '#activity-c-' . $comment->getId()
                        );
                    } else if ($entity->getAction() === 'mention' && $entity->getMention()->getUser()->isNotificationsEnabled()) {
                        $mention    = $entity->getMention();
                        $comment    = $mention->getComment();
                        $from       = $comment->getUser()->getName();
                        $emailTitle = $comment->getEmail()->getTitle();
                        $emailUrl   = $this->routeGenerator->generate('build_comments_redirect', [
                            'id'  => $entity->getId()
                        ], 'absolute');

                        $this->mentions->updateAll($comment, true);
                        $this->emailSender->sendNotificationMention(
                            $mention->getUser()->getEmail(),
                            $from,
                            str_replace('&nbsp;', ' ', strip_tags($comment->getContent())),
                            $dateTime,
                            $emailTitle,
                            $emailUrl . '#activity-c-' . $comment->getId()
                        );
                    }
                // }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }

    /**
     * @param object $entity
     *
     * @return int
     * @throws Exception
     */
    public function delete(object $entity): int
    {
        $this->redis->publish('notification-delete', json_encode([
            'id' => $entity->getId(),
            'to' => $entity->getTo()->getId(),
        ]));

        return parent::delete($entity);
    }

    /**
     * @param User   $to
     * @param string $status
     *
     * @return int
     * @throws Exception
     */
    public function updateStatusByTo(User $to, string $status): int
    {
        $stmt = $this->prepareAndExecute('UPDATE not_notifications SET not_status = ? WHERE not_to_id = ?', [
            $status,
            $to->getId(),
        ]);

        return $stmt->rowCount();
    }

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @Required()
     * @param Serializer $serializer
     */
    public function setSerializer(Serializer $serializer)
    {
    	$this->serializer = $serializer;
    }

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @Required()
     * @param UserRepository $userRepository
     */
    public function setUserRepository(UserRepository $userRepository)
    {
    	$this->userRepository = $userRepository;
    }

    /**
     * @var NotificationsMessageQueue
     */
    protected $notificationsMessageQueue;

    /**
     * @Required()
     * @param NotificationsMessageQueue $notificationsMessageQueue
     */
    public function setNotificationsMessageQueue(NotificationsMessageQueue $notificationsMessageQueue)
    {
    	$this->notificationsMessageQueue = $notificationsMessageQueue;
    }

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @Required()
     * @param Redis $redis
     */
    public function setRedis(Redis $redis)
    {
    	$this->redis = $redis;
    }

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @Required()
     * @param EmailSender $emailSender
     */
    public function setEmailSender(EmailSender $emailSender)
    {
    	$this->emailSender = $emailSender;
    }

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @Required()
     * @param RouteGeneratorInterface $routeGenerator
     */
    public function setRouteGenerator(RouteGeneratorInterface $routeGenerator)
    {
    	$this->routeGenerator = $routeGenerator;
    }

    /**
     * @var Mentions
     */
    protected $mentions;

    /**
     * @Required()
     * @param Mentions $mentions
     */
    public function setMentions(Mentions $mentions)
    {
    	$this->mentions = $mentions;
    }
}
